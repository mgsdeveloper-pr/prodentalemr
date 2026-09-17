<?php

namespace App\Services\Eligibility;

use App\Models\EligibilityConnection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class EligibilityProviderCatalog
{
    public const PROVIDERS = [
        'zuub' => 'Zuub',
        'dentalxchange' => 'DentalXChange',
        'pverify' => 'pVerify',
        'vyne' => 'Vyne / Onederful',
        'stedi' => 'Stedi',
    ];

    public function validateProvider(string $provider): void
    {
        abort_unless(array_key_exists($provider, self::PROVIDERS), 404);
    }

    public function storageReady(): bool
    {
        return Schema::hasTable('eligibility_connection_events')
            && Schema::hasColumn('eligibility_connections', 'eligibility_enabled');
    }

    public function rows(): array
    {
        $connections = $this->storageReady()
            ? EligibilityConnection::where('environment', 'production')->get()->keyBy('provider')
            : collect();

        return collect(self::PROVIDERS)->map(function (string $name, string $provider) use ($connections): array {
            return [
                'provider' => $provider,
                'name' => $name,
                'enabled' => (bool) $connections->get($provider)?->eligibility_enabled,
                'ready' => $this->readyForEligibility($provider),
                'status' => match ($provider) {
                    'zuub' => 'Awaiting API details',
                    'vyne' => $connections->get($provider)?->last_check_status === 'authenticated' ? 'Authenticated; validation pending' : 'API testing available',
                    'dentalxchange' => $connections->get($provider)?->last_check_status === 'healthy' ? 'Health checked; validation pending' : 'API key required',
                    'pverify' => $connections->get($provider)?->last_check_status === 'authenticated' ? 'Authenticated; validation pending' : 'Credentials required',
                    'stedi' => $connections->get($provider)?->last_check_status === 'directory_access' ? 'Directory checked; validation pending' : 'API key required',
                    default => 'Not configured',
                },
            ];
        })->values()->all();
    }

    public function readyForEligibility(string $provider): bool
    {
        $this->validateProvider($provider);

        // No verified eligibility adapters/enrollment checks have been delivered yet.
        // A connectivity probe or a saved credential is not eligibility readiness.
        return false;
    }

    public function setEnabled(User $actor, string $provider, bool $enabled): void
    {
        app(ZuubConnectionService::class)->authorize($actor);
        $this->validateProvider($provider);
        abort_unless($this->storageReady(), 503);
        if ($enabled && ! $this->readyForEligibility($provider)) {
            throw ValidationException::withMessages(['provider_activation' => 'Complete provider setup, enrollment and eligibility testing before enabling this connection.']);
        }

        DB::transaction(function () use ($actor, $provider, $enabled): void {
            $connection = EligibilityConnection::firstOrCreate(['provider' => $provider, 'environment' => 'production']);
            $connection = EligibilityConnection::lockForUpdate()->findOrFail($connection->id);
            if ((bool) $connection->eligibility_enabled === $enabled) {
                return;
            }
            $connection->eligibility_enabled = $enabled;
            $connection->updated_by = $actor->id;
            $connection->save();
            $connection->events()->create(['user_id' => $actor->id, 'event' => 'eligibility_activation', 'status' => $enabled ? 'enabled' : 'disabled']);
        });
    }
}
