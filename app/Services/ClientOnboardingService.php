<?php

namespace App\Services;

use App\Filament\Saas\Pages\DsoOnboarding;
use App\Filament\Saas\Pages\TenantOnboarding;
use App\Models\Dso;
use App\Models\OnboardingDraft;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ClientOnboardingService
{
    public const STRUCTURES = ['single_clinic', 'organization', 'dso'];
    public const VERIFICATION_MODELS = ['self_service', 'managed_service', 'hybrid'];

    public function start(
        User $user,
        string $accountStructure,
        string $verificationModel,
        string $entryPoint = 'internal',
    ): OnboardingDraft {
        abort_unless(in_array($accountStructure, self::STRUCTURES, true), 422, 'Invalid account structure.');
        abort_unless(in_array($verificationModel, self::VERIFICATION_MODELS, true), 422, 'Invalid verification model.');

        return OnboardingDraft::create([
            'user_id' => $user->getKey(),
            'type' => $accountStructure === 'dso' ? 'dso_onboarding' : 'organization_onboarding',
            'entry_point' => $entryPoint,
            'account_structure' => $accountStructure,
            'verification_model' => $verificationModel,
            'status' => OnboardingDraft::STATUS_DRAFT,
            'last_completed_step' => 1,
            'data' => [
                'client_type' => $accountStructure === 'dso' ? 'organization' : $accountStructure,
                'verification_model' => $verificationModel,
            ],
            'expires_at' => $entryPoint === 'internal' ? null : now()->addDays(14),
        ]);
    }

    public function findForUser(?string $reference, User $user, ?string $type = null): ?OnboardingDraft
    {
        if (blank($reference)) {
            return null;
        }

        $draft = OnboardingDraft::query()
            ->where('public_id', $reference)
            ->when($type, fn ($query) => $query->where('type', $type))
            ->first();

        if (! $draft || (int) $draft->user_id !== (int) $user->getKey()) {
            throw new AuthorizationException('This onboarding record is not available to this user.');
        }

        return $draft;
    }

    public function save(OnboardingDraft $draft, array $data, int $lastCompletedStep): OnboardingDraft
    {
        $draft->fill([
            'status' => OnboardingDraft::STATUS_DRAFT,
            'account_structure' => $draft->account_structure ?: ($data['client_type'] ?? 'organization'),
            'verification_model' => $data['verification_model'] ?? $draft->verification_model,
            'last_completed_step' => $lastCompletedStep,
            'data' => $this->sanitize($data),
        ])->save();

        return $draft;
    }

    public function activate(
        OnboardingDraft $draft,
        Organization $organization,
        ?Dso $dso = null,
    ): OnboardingDraft {
        $summary = collect($draft->data ?? [])->only([
            'client_type',
            'verification_model',
            'organization_name',
            'clinic_name',
            'location_name',
            'owner_name',
            'owner_email',
            'dso_name',
            'dso_admin_name',
            'dso_admin_email',
            'subscription_plan_id',
        ])->all();

        $draft->fill([
            'status' => OnboardingDraft::STATUS_ACTIVATED,
            'organization_id' => $organization->getKey(),
            'dso_id' => $dso?->getKey(),
            'activated_at' => now(),
            'data' => $summary,
            'notification_sent_at' => null,
        ])->save();

        return $draft;
    }

    public function resumeUrl(OnboardingDraft $draft): string
    {
        $parameters = [
            'onboarding' => $draft->public_id,
            'verification_model' => $draft->verification_model,
        ];

        if ($draft->account_structure === 'dso') {
            return DsoOnboarding::getUrl($parameters, panel: 'saas');
        }

        return TenantOnboarding::getUrl([
            ...$parameters,
            'client_type' => $draft->account_structure,
        ], panel: 'saas');
    }

    protected function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (str_contains(strtolower((string) $key), 'password')) {
                continue;
            }

            $sanitized[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $sanitized;
    }
}
