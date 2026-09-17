<?php

namespace App\Services\Eligibility;

use App\Models\EligibilityConnection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class VyneConnectionService
{
    public const SANDBOX_PAYERS = ['PRINCIPAL', 'AETNA_DENTAL_PLANS', 'DD_CALIFORNIA', 'METLIFE'];

    public function connection(string $environment): EligibilityConnection
    {
        abort_unless(in_array($environment, ['sandbox', 'production'], true), 422);

        return EligibilityConnection::firstOrCreate(['provider' => 'vyne', 'environment' => $environment]);
    }

    public function save(User $actor, string $environment, array $input): void
    {
        app(ZuubConnectionService::class)->authorize($actor);
        validator($input, [
            'client_id' => ['nullable', 'string', 'max:1024', 'not_regex:/[\r\n]/'],
            'client_secret' => ['nullable', 'string', 'max:2048', 'not_regex:/[\r\n]/'],
            'enabled' => ['required', 'boolean'],
            'remove_key' => ['sometimes', 'boolean'],
        ])->validate();

        DB::transaction(function () use ($actor, $environment, $input): void {
            $connection = $this->connection($environment);
            $connection = EligibilityConnection::lockForUpdate()->findOrFail($connection->id);
            if ($input['remove_key'] ?? false) {
                $connection->api_key = null;
            } elseif (filled($input['client_id'] ?? null) || filled($input['client_secret'] ?? null)) {
                if ($environment !== 'production' || blank($input['client_id'] ?? null) || blank($input['client_secret'] ?? null)) {
                    throw ValidationException::withMessages(['data.client_id' => 'Enter both production Client ID and Client Secret together. Sandbox does not use credentials.']);
                }
                // The existing encrypted credential column holds the atomic OAuth credential pair.
                $connection->api_key = json_encode(['client_id' => trim($input['client_id']), 'client_secret' => trim($input['client_secret'])], JSON_THROW_ON_ERROR);
            }
            if ($environment === 'production' && $input['enabled'] && blank($connection->api_key)) {
                throw ValidationException::withMessages(['data.client_id' => 'Save production credentials before enabling authentication tests.']);
            }
            $connection->enabled = (bool) $input['enabled'];
            if ($connection->isDirty(['api_key', 'enabled'])) {
                $connection->last_checked_at = null;
                $connection->last_check_status = null;
                $connection->eligibility_enabled = false;
            }
            $connection->updated_by = $actor->id;
            $connection->save();
            $connection->events()->create(['user_id' => $actor->id, 'event' => 'settings_saved', 'status' => $connection->enabled ? 'tests_enabled' : 'disabled']);
        });
    }

    public function readiness(EligibilityConnection $connection): array
    {
        return [
            ['label' => 'Documented API endpoints', 'ready' => true],
            ['label' => $connection->environment === 'sandbox' ? 'Sandbox requires no credentials' : 'Production credentials stored', 'ready' => $connection->environment === 'sandbox' || filled($connection->api_key)],
            ['label' => 'Connection tests enabled', 'ready' => $connection->enabled],
            ['label' => 'Successful saved connection test', 'ready' => in_array($connection->last_check_status, ['sandbox_received', 'authenticated'], true)],
            ['label' => 'Clinic/payer mapping and production validation', 'ready' => false],
        ];
    }

    public function probe(User $actor, string $environment, string $payer = 'PRINCIPAL'): array
    {
        app(ZuubConnectionService::class)->authorize($actor);
        abort_unless(in_array($payer, self::SANDBOX_PAYERS, true), 422);
        $connection = $this->connection($environment);
        if (! $connection->enabled) {
            return $this->record($connection, $actor, 'blocked', 'Enable and save connection tests first. No request was sent.');
        }
        $limiter = 'vyne-test:'.$connection->id;
        if (RateLimiter::tooManyAttempts($limiter, 2)) {
            return ['status' => 'limited', 'message' => 'Wait one minute before testing again.'];
        }
        RateLimiter::hit($limiter, 60);

        try {
            $http = Http::acceptJson()->asJson()->connectTimeout(5)->timeout(30)->withOptions(['allow_redirects' => false]);
            if ($environment === 'sandbox') {
                // Fixed vendor test identity only. Never accept or load local patient information.
                $response = $http->post('https://sandbox.onederful.co/sandbox/eligibility', [
                    'subscriber' => ['first_name' => 'TEST', 'last_name' => 'PERSON', 'dob' => '01/01/2011', 'member_id' => '1234567890'],
                    'provider' => ['npi' => '1234567890'],
                    'payer' => ['id' => $payer],
                    'version' => 'v2',
                ]);
            } else {
                $credentials = json_decode($connection->api_key ?? '', true);
                if (! is_array($credentials) || blank($credentials['client_id'] ?? null) || blank($credentials['client_secret'] ?? null)) {
                    return $this->record($connection, $actor, 'blocked', 'Save a complete production credential pair first.');
                }
                $response = $http->post('https://production.onederful.co/oauth2/token', [
                    'client_id' => $credentials['client_id'], 'client_secret' => $credentials['client_secret'],
                ]);
            }
            if (! $response->successful()) {
                $status = match ($response->status()) {
                    401, 403 => 'unauthorized', 429 => 'limited', default => 'failed'
                };

                return $this->record($connection, $actor, $status, 'Vyne test did not succeed (HTTP '.$response->status().'). No automatic retry was made.');
            }
            $body = $response->json();
            if (! is_array($body) || isset($body['errors']) || isset($body['code'])) {
                return $this->record($connection, $actor, 'failed', 'Vyne returned an error or an unexpected response. No benefits were applied.');
            }
            if ($environment === 'production') {
                // Token stays server-side and is discarded; expiry units in the public docs are ambiguous.
                if (! is_string($body['access_token'] ?? null) || blank($body['access_token']) || ($body['token_type'] ?? '') !== 'Bearer') {
                    return $this->record($connection, $actor, 'failed', 'Vyne did not return a valid authentication response.');
                }
                $scope = is_string($body['scope'] ?? null) ? preg_split('/\s+/', trim($body['scope'])) : [];
                if (! in_array('feature:eligibility', $scope, true)) {
                    return $this->record($connection, $actor, 'scope_missing', 'Authentication responded, but eligibility permission was not confirmed. Contact Vyne.');
                }

                return $this->record($connection, $actor, 'authenticated', 'Production authentication succeeded with eligibility permission. No patient request was sent.');
            }
            if (! is_array($body['patient'] ?? null) && ! is_array($body['subscriber'] ?? null)) {
                return $this->record($connection, $actor, 'failed', 'The sandbox response did not match the documented benefit format.');
            }
            $result = $this->record($connection, $actor, 'sandbox_received', 'Vyne sandbox benefits received. Static test data only; not proof of live payer coverage.');
            $result['coverage'] = in_array(data_get($body, 'patient.coverage.status'), ['active', 'inactive'], true) ? data_get($body, 'patient.coverage.status') : 'Not returned';
            $result['benefits'] = $this->benefits($body);

            return $result;
        } catch (Throwable) {
            return $this->record($connection, $actor, 'failed', 'Vyne connection could not be completed. No automatic retry was made.');
        }
    }

    public function benefits(array $body): array
    {
        $rows = [];
        foreach (['deductible' => 'Deductible', 'maximums' => 'Maximum', 'coinsurance' => 'Coverage percentage'] as $key => $label) {
            foreach (array_slice(is_array($body[$key] ?? null) ? $body[$key] : [], 0, 100) as $benefit) {
                if (! is_array($benefit)) {
                    continue;
                }
                $value = $benefit[$key === 'coinsurance' ? 'percent' : 'amount'] ?? null;
                $rows[] = [
                    'label' => $label,
                    'category' => $this->text($benefit['category'] ?? null),
                    'network' => $this->text($benefit['network'] ?? null),
                    'period' => $this->text($benefit['plan_period'] ?? null),
                    'level' => $this->text($benefit['coverage_level'] ?? null),
                    'value' => is_numeric($value) ? (string) $value.($key === 'coinsurance' ? '%' : '') : 'Not returned',
                ];
            }
        }

        return $rows;
    }

    private function text(mixed $value): string
    {
        return is_string($value) ? str_replace('_', ' ', mb_substr($value, 0, 120)) : 'Not returned';
    }

    private function record(EligibilityConnection $connection, User $actor, string $status, string $message): array
    {
        $connection->update(['last_checked_at' => now(), 'last_check_status' => $status]);
        $connection->events()->create(['user_id' => $actor->id, 'event' => 'connection_test', 'status' => $status]);

        return compact('status', 'message');
    }
}
