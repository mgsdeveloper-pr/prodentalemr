<?php

namespace App\Services;

use App\Filament\Saas\Pages\DsoOnboarding;
use App\Filament\Saas\Pages\TenantOnboarding;
use App\Models\Dso;
use App\Models\OnboardingDraft;
use App\Models\Organization;
use App\Models\User;
use App\Models\WebsiteRegistration;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ClientOnboardingService
{
    public const STRUCTURES = ['single_clinic', 'organization', 'dso'];

    public const VERIFICATION_MODELS = ['self_service', 'managed_service', 'hybrid'];

    public function start(
        ?User $user,
        string $accountStructure,
        string $verificationModel,
        string $entryPoint = 'internal',
    ): OnboardingDraft {
        abort_unless(in_array($accountStructure, self::STRUCTURES, true), 422, 'Invalid account structure.');
        abort_unless(in_array($verificationModel, self::VERIFICATION_MODELS, true), 422, 'Invalid verification model.');
        abort_unless($user || $entryPoint === 'website', 422, 'An internal draft requires a user.');

        return OnboardingDraft::create([
            'user_id' => $user?->getKey(),
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
        return DB::transaction(fn () => $this->findAndClaimForUser($reference, $user, $type));
    }

    private function findAndClaimForUser(?string $reference, User $user, ?string $type): ?OnboardingDraft
    {
        if (blank($reference)) {
            return null;
        }

        $draft = OnboardingDraft::query()
            ->where('public_id', $reference)
            ->when($type, fn ($query) => $query->where('type', $type))
            ->lockForUpdate()
            ->first();

        $canReviewWebsite = $draft?->entry_point === 'website'
            && in_array($draft->status, ['submitted', 'under_review', 'changes_requested'], true)
            && WebsiteRegistration::where('onboarding_draft_id', $draft->id)->whereNotNull('email_verified_at')->exists()
            && $user->canPerformSaasModuleAction('organizations', 'update');
        if (! $draft || (! $canReviewWebsite && (int) $draft->user_id !== (int) $user->getKey())) {
            throw new AuthorizationException('This onboarding record is not available to this user.');
        }

        if ($canReviewWebsite) {
            $draft->update(['status' => OnboardingDraft::STATUS_UNDER_REVIEW]);
        }

        return $draft;
    }

    public function save(OnboardingDraft $draft, array $data, int $lastCompletedStep): OnboardingDraft
    {
        return DB::transaction(function () use ($draft, $data, $lastCompletedStep): OnboardingDraft {
            $draft = $this->lockEditable($draft);
            if ($draft->entry_point === 'website' && isset($draft->data['plan_snapshot']) && ! array_key_exists('plan_snapshot', $data)) {
                $data['plan_snapshot'] = $draft->data['plan_snapshot'];
            }
            $draft->fill([
                'status' => $draft->entry_point === 'website' && $draft->status === OnboardingDraft::STATUS_UNDER_REVIEW
                    ? OnboardingDraft::STATUS_UNDER_REVIEW : OnboardingDraft::STATUS_DRAFT,
                'account_structure' => $draft->account_structure ?: ($data['client_type'] ?? 'organization'),
                'verification_model' => $data['verification_model'] ?? $draft->verification_model,
                'last_completed_step' => $lastCompletedStep,
                'data' => $this->sanitize($data),
            ])->save();

            return $draft;
        });
    }

    public function lockEditable(OnboardingDraft $draft): OnboardingDraft
    {
        $draft = OnboardingDraft::whereKey($draft->getKey())->lockForUpdate()->firstOrFail();
        abort_if($draft->status === OnboardingDraft::STATUS_ACTIVATED || $draft->activated_at !== null, 409, 'This onboarding is complete. Open the existing account instead.');

        return $draft;
    }

    public function activate(
        OnboardingDraft $draft,
        Organization $organization,
        ?Dso $dso = null,
    ): OnboardingDraft {
        return DB::transaction(function () use ($draft, $organization, $dso): OnboardingDraft {
            $draft = $this->lockEditable($draft);
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
                'plan_snapshot',
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
        });
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
