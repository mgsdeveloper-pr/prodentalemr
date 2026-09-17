<?php

namespace App\Services\Eligibility;

use App\Models\EligibilityConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class PVerifyConnectionService extends ZuubConnectionService
{
    public function connection(string $environment): EligibilityConnection
    {
        $this->validateEnvironment($environment);

        return EligibilityConnection::firstOrCreate(['provider' => 'pverify', 'environment' => $environment]);
    }

    public function save(User $actor, string $environment, array $input): void
    {
        $this->authorize($actor);
        $this->validateEnvironment($environment);
        validator($input, [
            'client_id' => ['nullable', 'required_with:client_secret', 'string', 'max:1024', 'not_regex:/[\r\n]/'],
            'client_secret' => ['nullable', 'required_with:client_id', 'string', 'max:2048', 'not_regex:/[\r\n]/'],
        ])->validate();
        // Ignore arbitrary api_key input; only a complete validated pair can replace credentials.
        $input['api_key'] = filled($input['client_id'] ?? null)
            ? json_encode(['client_id' => trim($input['client_id']), 'client_secret' => trim($input['client_secret'])], JSON_THROW_ON_ERROR)
            : null;
        parent::save($actor, $environment, $input);
    }

    public function readiness(EligibilityConnection $connection): array
    {
        return [
            ['label' => 'Client API ID and secret stored', 'ready' => filled($connection->api_key)],
            ['label' => 'Authentication tests enabled', 'ready' => $connection->enabled],
            ['label' => 'Documented token endpoint', 'ready' => true],
            ['label' => 'Successful authentication test', 'ready' => $connection->last_check_status === 'authenticated'],
            ['label' => 'Dental API access and clinic/payer mapping', 'ready' => false],
            ['label' => 'Production eligibility validation', 'ready' => false],
        ];
    }

    public function probe(User $actor, string $environment): array
    {
        $this->authorize($actor);
        $connection = $this->connection($environment);
        $pair = json_decode($connection->api_key ?? '', true);
        if (! $connection->enabled || ! is_array($pair) || blank($pair['client_id'] ?? null) || blank($pair['client_secret'] ?? null)) {
            return $this->record($connection, $actor, 'blocked', 'Save both credentials and enable authentication tests first. No request was sent.');
        }
        $limiter = 'pverify-auth:'.$connection->id;
        if (RateLimiter::tooManyAttempts($limiter, 2)) {
            return ['status' => 'limited', 'message' => 'Wait one minute before testing again.'];
        }
        RateLimiter::hit($limiter, 60);

        try {
            $url = $environment === 'sandbox' ? 'https://testapi.pverify.com/Token' : 'https://api.pverify.com/Token';
            $response = Http::acceptJson()->asForm()->connectTimeout(5)->timeout(20)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders(['Client-API-Id' => $pair['client_id']])
                ->post($url, ['Client_Id' => $pair['client_id'], 'Client_Secret' => $pair['client_secret'], 'grant_type' => 'client_credentials']);
            if (! $response->successful()) {
                $status = match ($response->status()) {
                    401, 403 => 'unauthorized', 429 => 'limited', default => 'failed'
                };

                return $this->record($connection, $actor, $status, 'pVerify authentication did not succeed (HTTP '.$response->status().'). Confirm credentials and API access with pVerify.');
            }
            $body = $response->json();
            if (! is_array($body) || isset($body['error']) || ! is_string($body['access_token'] ?? null)
                || blank($body['access_token']) || strtolower((string) ($body['token_type'] ?? '')) !== 'bearer'
                || ! is_numeric($body['expires_in'] ?? null) || $body['expires_in'] <= 0) {
                return $this->record($connection, $actor, 'failed', 'pVerify did not return a valid authentication response. Access is not confirmed.');
            }

            // Token is discarded and never sent to Livewire, persisted, or logged.
            return $this->record($connection, $actor, 'authenticated', 'pVerify authentication succeeded. No patient request was sent; dental API and payer access still require validation.');
        } catch (Throwable) {
            return $this->record($connection, $actor, 'failed', 'pVerify authentication could not be completed. No automatic retry was made.');
        }
    }
}
