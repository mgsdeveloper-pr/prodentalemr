<?php

namespace App\Services\Eligibility;

use App\Models\EligibilityConnection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class ZuubConnectionService
{
    public function authorize(User $actor): void
    {
        abort_unless($actor->isSaasAdmin() && $actor->canAccessSaasModule('settings'), 403);
    }

    public function connection(string $environment): EligibilityConnection
    {
        $this->validateEnvironment($environment);

        return EligibilityConnection::firstOrCreate(['provider' => 'zuub', 'environment' => $environment]);
    }

    public function save(User $actor, string $environment, array $input): void
    {
        $this->authorize($actor);
        $this->validateEnvironment($environment);
        validator($input, [
            'api_key' => ['nullable', 'string', 'max:4096', 'not_regex:/[\r\n]/'],
            'enabled' => ['required', 'boolean'],
            'remove_key' => ['sometimes', 'boolean'],
        ])->validate();

        DB::transaction(function () use ($actor, $environment, $input): void {
            $connection = $this->connection($environment);
            $connection = EligibilityConnection::lockForUpdate()->findOrFail($connection->id);
            if ($input['remove_key'] ?? false) {
                $connection->api_key = null;
            } elseif (filled($input['api_key'] ?? null)) {
                $connection->api_key = trim($input['api_key']);
            }
            if ($input['enabled'] && blank($connection->api_key)) {
                throw ValidationException::withMessages(['data.api_key' => 'Save a credential before enabling connection tests.']);
            }
            $connection->enabled = (bool) $input['enabled'];
            if ($connection->isDirty(['api_key', 'enabled'])) {
                $connection->last_checked_at = null;
                $connection->last_check_status = null;
            }
            $connection->updated_by = $actor->id;
            $connection->save();
            $connection->events()->create(['user_id' => $actor->id, 'event' => 'settings_saved', 'status' => $connection->enabled ? 'tests_enabled' : 'disabled']);
        });
    }

    public function readiness(EligibilityConnection $connection): array
    {
        return [
            ['label' => 'Credential stored', 'ready' => filled($connection->api_key)],
            ['label' => 'Connection tests enabled', 'ready' => $connection->enabled],
            ['label' => 'Documented connection-test endpoint', 'ready' => $this->probeConfiguration($connection) !== null],
            ['label' => 'Eligibility request and response mapping', 'ready' => false],
            ['label' => 'Payer and clinic enrollment confirmed', 'ready' => false],
        ];
    }

    public function probe(User $actor, string $environment): array
    {
        $this->authorize($actor);
        $connection = $this->connection($environment);
        $config = $this->probeConfiguration($connection);
        if (! $connection->enabled || blank($connection->api_key) || $config === null) {
            return $this->record($connection, $actor, 'blocked', 'No request sent. Save credentials and confirm the documented, non-patient connection-test endpoint first.');
        }
        $limiter = 'zuub-probe:'.$connection->id;
        if (RateLimiter::tooManyAttempts($limiter, 2)) {
            return ['status' => 'limited', 'message' => 'Please wait one minute before another connection test.'];
        }
        RateLimiter::hit($limiter, 60);

        try {
            // No retries, redirects, patient payloads, or response bodies in logs/UI.
            $response = Http::connectTimeout(5)->timeout(15)
                ->withOptions(['allow_redirects' => false])
                ->acceptJson()
                ->withHeaders([$config['credential_header'] => $config['credential_prefix'].$connection->api_key])
                ->get($config['probe_url']);
            [$status, $message] = match (true) {
                $response->successful() => ['reachable', 'Endpoint responded. This does not confirm eligibility access, payer enrollment, or benefit completeness.'],
                in_array($response->status(), [401, 403], true) => ['unauthorized', 'Credential rejected or access is not permitted. Confirm credentials and permissions with Zuub.'],
                $response->status() === 429 => ['limited', 'Zuub rate limit reached. No automatic retry was made.'],
                default => ['failed', 'Connection test failed. Confirm the endpoint and account status with Zuub.'],
            };
        } catch (Throwable) {
            [$status, $message] = ['failed', 'Connection could not be completed. No automatic retry was made.'];
        }

        return $this->record($connection, $actor, $status, $message);
    }

    protected function probeConfiguration(EligibilityConnection $connection): ?array
    {
        $config = config('eligibility.zuub.'.$connection->environment, []);
        $url = parse_url((string) ($config['probe_url'] ?? ''));
        $host = strtolower((string) ($url['host'] ?? ''));
        $header = (string) ($config['credential_header'] ?? '');
        if (! ($config['probe_documented_safe'] ?? false)
            || ($url['scheme'] ?? '') !== 'https'
            || $host === '' || $host !== strtolower((string) ($config['approved_host'] ?? ''))
            || filter_var($host, FILTER_VALIDATE_IP) || ! str_contains($host, '.')
            || preg_match('/(?:localhost|\.local|\.internal)$/i', $host)
            || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])
            || (isset($url['port']) && $url['port'] !== 443)
            || ! preg_match('/^[A-Za-z][A-Za-z0-9-]{0,63}$/', $header)
            || in_array(strtolower($header), ['host', 'cookie', 'content-length', 'connection'], true)
            || preg_match('/[\r\n]/', (string) ($config['credential_prefix'] ?? ''))) {
            return null;
        }

        return $config;
    }

    protected function record(EligibilityConnection $connection, User $actor, string $status, string $message): array
    {
        $connection->update(['last_checked_at' => now(), 'last_check_status' => $status]);
        $connection->events()->create(['user_id' => $actor->id, 'event' => 'connection_test', 'status' => $status]);

        return compact('status', 'message');
    }

    protected function validateEnvironment(string $environment): void
    {
        abort_unless(in_array($environment, ['sandbox', 'production'], true), 422);
    }
}
