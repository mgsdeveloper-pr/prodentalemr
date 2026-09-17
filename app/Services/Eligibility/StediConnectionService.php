<?php

namespace App\Services\Eligibility;

use App\Models\EligibilityConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class StediConnectionService extends ZuubConnectionService
{
    public function connection(string $environment): EligibilityConnection
    {
        $this->validateEnvironment($environment);

        return EligibilityConnection::firstOrCreate(['provider' => 'stedi', 'environment' => $environment]);
    }

    public function readiness(EligibilityConnection $connection): array
    {
        return [
            ['label' => 'API key stored', 'ready' => filled($connection->api_key)],
            ['label' => 'Directory connection tests enabled', 'ready' => $connection->enabled],
            ['label' => 'Documented read-only payer endpoint', 'ready' => true],
            ['label' => 'Payer directory access confirmed', 'ready' => $connection->last_check_status === 'directory_access'],
            ['label' => 'Key mode and eligibility access verified', 'ready' => false],
            ['label' => 'Clinic/payer mapping and production validation', 'ready' => false],
        ];
    }

    public function probe(User $actor, string $environment): array
    {
        $this->authorize($actor);
        $connection = $this->connection($environment);
        if (! $connection->enabled || blank($connection->api_key)) {
            return $this->record($connection, $actor, 'blocked', 'Save an API key and enable directory tests first. No request was sent.');
        }
        $limiter = 'stedi-directory:'.$connection->id;
        if (RateLimiter::tooManyAttempts($limiter, 2)) {
            return ['status' => 'limited', 'message' => 'Wait one minute before testing again.'];
        }
        RateLimiter::hit($limiter, 60);

        try {
            // Both key modes use the same API host. Read only, one bounded page, no patient transaction.
            $response = Http::acceptJson()->connectTimeout(5)->timeout(20)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders(['Authorization' => $connection->api_key])
                ->get('https://payers.us.stedi.com/2024-04-01/payers', ['pageSize' => 10]);
            if (! $response->successful()) {
                $status = match ($response->status()) {
                    401, 403 => 'unauthorized', 429 => 'limited', default => 'failed'
                };

                return $this->record($connection, $actor, $status, 'Stedi directory access was not confirmed (HTTP '.$response->status().'). Verify key permissions; test keys may not support this endpoint. No eligibility request was sent.');
            }
            $body = $response->json();
            if (! is_array($body) || ! is_array($body['items'] ?? null) || ! array_is_list($body['items'])) {
                return $this->record($connection, $actor, 'failed', 'Stedi returned an unexpected directory response. Access is not confirmed.');
            }
            foreach ($body['items'] as $payer) {
                if (! is_array($payer) || ! is_string($payer['stediId'] ?? null) || ! is_string($payer['displayName'] ?? null)) {
                    return $this->record($connection, $actor, 'failed', 'Stedi returned an unexpected payer record. Access is not confirmed.');
                }
            }

            return $this->record($connection, $actor, 'directory_access', 'Stedi payer directory responded. No patient data was sent. Key mode, dental eligibility access and enrollment are not confirmed by this test.');
        } catch (Throwable) {
            return $this->record($connection, $actor, 'failed', 'Stedi connection could not be completed. No automatic retry was made.');
        }
    }
}
