<?php

namespace App\Services\Eligibility;

use App\Models\EligibilityConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class DentalXChangeConnectionService extends ZuubConnectionService
{
    public function connection(string $environment): EligibilityConnection
    {
        $this->validateEnvironment($environment);

        return EligibilityConnection::firstOrCreate(['provider' => 'dentalxchange', 'environment' => $environment]);
    }

    public function readiness(EligibilityConnection $connection): array
    {
        return [
            ['label' => 'API key stored', 'ready' => filled($connection->api_key)],
            ['label' => 'Health checks enabled', 'ready' => $connection->enabled],
            ['label' => 'Documented XConnect health endpoint', 'ready' => true],
            ['label' => 'Successful health check', 'ready' => $connection->last_check_status === 'healthy'],
            ['label' => 'Account access and clinic/payer mapping', 'ready' => false],
            ['label' => 'Production eligibility validation', 'ready' => false],
        ];
    }

    public function probe(User $actor, string $environment): array
    {
        $this->authorize($actor);
        $connection = $this->connection($environment);
        if (! $connection->enabled || blank($connection->api_key)) {
            return $this->record($connection, $actor, 'blocked', 'Save an API key and enable health checks first. No request was sent.');
        }
        $limiter = 'dentalxchange-health:'.$connection->id;
        if (RateLimiter::tooManyAttempts($limiter, 2)) {
            return ['status' => 'limited', 'message' => 'Wait one minute before testing again.'];
        }
        RateLimiter::hit($limiter, 60);

        try {
            // Fixed vendor endpoint; no account passwords, patient payloads, redirects or retries.
            $path = $environment === 'sandbox' ? '/sandbox/eligibility/health' : '/eligibility/health';
            $response = Http::acceptJson()->connectTimeout(5)->timeout(20)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders(['API-Key' => $connection->api_key])
                ->get('https://api.dentalxchange.com'.$path);
            if (! $response->successful()) {
                $status = match ($response->status()) {
                    401, 403 => 'unauthorized', 429 => 'limited', default => 'failed'
                };

                return $this->record($connection, $actor, $status, 'DentalXChange health check did not succeed (HTTP '.$response->status().'). Confirm API access with DentalXChange.');
            }
            $body = $response->json();
            if (! is_array($body) || ! is_bool($body['healthy'] ?? null)) {
                return $this->record($connection, $actor, 'failed', 'DentalXChange returned an unexpected health response. Access is not confirmed.');
            }
            if (! $body['healthy']) {
                return $this->record($connection, $actor, 'unhealthy', 'DentalXChange reports an unhealthy service. No eligibility request was sent.');
            }

            return $this->record($connection, $actor, 'healthy', 'DentalXChange reports a healthy service. This does not verify account access, payer enrollment or patient benefits.');
        } catch (Throwable) {
            return $this->record($connection, $actor, 'failed', 'DentalXChange could not be reached. No automatic retry was made.');
        }
    }
}
