<?php

namespace App\Support;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemActivity;
use App\Models\SaasSetting;
use App\Models\User;
use App\Models\VerificationNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;

class VerificationNotificationCenter
{
    protected const LEVELS = [
        'managed_service_requested' => 'warning',
        'admin_import_created' => 'warning',
        'assignment_changed' => 'info',
        'status_changed' => 'info',
        'outcome_changed' => 'success',
        'clinic_verification_updated' => 'info',
        'clinic_self_service_created' => 'success',
        'verification_request_created' => 'success',
        'verification_profile_saved' => 'info',
        'verification_pdf_download' => 'info',
        'verification_pdf_preview' => 'info',
        'urgent_priority_flagged' => 'danger',
        'urgent_priority_assigned' => 'danger',
        'info_requested_from_clinic' => 'warning',
        'clinic_response_received' => 'info',
        'returned_for_rework' => 'danger',
        'rework_resumed' => 'info',
        'rework_completed' => 'success',
        'sla_due_today' => 'warning',
        'sla_overdue' => 'danger',
    ];

    protected const EVENT_SETTING_MAP = [
        'managed_service_requested' => 'verification_notify_on_managed_service_requested',
        'clinic_self_service_created' => 'verification_notify_on_clinic_self_service_created',
        'verification_request_created' => 'verification_notify_on_verification_request_created',
        'admin_import_created' => 'verification_notify_on_admin_import_created',
        'assignment_changed' => 'verification_notify_on_assignment_changed',
        'status_changed' => 'verification_notify_on_status_changed',
        'outcome_changed' => 'verification_notify_on_outcome_changed',
        'clinic_verification_updated' => 'verification_notify_on_clinic_verification_updated',
        'verification_profile_saved' => 'verification_notify_on_verification_profile_saved',
        'verification_pdf_download' => 'verification_notify_on_verification_pdf_download',
        'verification_pdf_preview' => 'verification_notify_on_verification_pdf_preview',
        'urgent_priority_flagged' => 'verification_notify_on_urgent_flagged',
        'urgent_priority_assigned' => 'verification_notify_on_urgent_assigned',
        'info_requested_from_clinic' => 'verification_notify_on_status_changed',
        'clinic_response_received' => 'verification_notify_on_clinic_verification_updated',
        'returned_for_rework' => 'verification_notify_on_status_changed',
        'rework_resumed' => 'verification_notify_on_status_changed',
        'rework_completed' => 'verification_notify_on_status_changed',
        'sla_due_today' => 'verification_notify_on_sla_alert',
        'sla_overdue' => 'verification_notify_on_sla_alert',
    ];

    public static function topbarNotificationsFor(string $panel, Authenticatable|User|null $user, ?int $clinicId = null, int $limit = 8): Collection
    {
        if (! $user instanceof User) {
            return new Collection();
        }

        return VerificationNotification::query()
            ->where('user_id', $user->getKey())
            ->where('panel', $panel)
            ->when(filled($clinicId), fn ($query) => $query->where('clinic_id', $clinicId))
            ->orderByRaw("case when level = 'danger' and read_at is null then 0 when read_at is null then 1 else 2 end")
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public static function unreadCountFor(string $panel, Authenticatable|User|null $user, ?int $clinicId = null): int
    {
        if (! $user instanceof User) {
            return 0;
        }

        return VerificationNotification::query()
            ->where('user_id', $user->getKey())
            ->where('panel', $panel)
            ->whereNull('read_at')
            ->when(filled($clinicId), fn ($query) => $query->where('clinic_id', $clinicId))
            ->count();
    }

    public static function topbarAlertFor(string $panel, Authenticatable|User|null $user, ?int $clinicId = null): ?VerificationNotification
    {
        if (! $user instanceof User) {
            return null;
        }

        return VerificationNotification::query()
            ->where('user_id', $user->getKey())
            ->where('panel', $panel)
            ->whereNull('read_at')
            ->when(filled($clinicId), fn ($query) => $query->where('clinic_id', $clinicId))
            ->whereIn('level', ['danger', 'warning'])
            ->latest('created_at')
            ->first();
    }

    public static function dispatchForActivity(BillingWorkItemActivity $activity): void
    {
        $activity->loadMissing([
            'user',
            'workItem.clinic.organization',
            'workItem.patient',
            'workItem.provider',
            'workItem.assignedTo',
        ]);

        $workItem = $activity->workItem;

        if (! $workItem || blank($workItem->clinic_id)) {
            return;
        }

        $settings = SaasSetting::current();

        if (! static::eventEnabled($activity->activity_type, $settings)) {
            return;
        }

        $payload = static::payload($activity, $workItem);
        $deliveries = [];

        if ($settings->verification_notify_admin_all) {
            foreach (static::adminRecipients() as $recipient) {
                $deliveries['verification:' . $recipient->getKey()] = [
                    'panel' => 'verification',
                    'recipient' => $recipient,
                ];
            }
        }

        if ($settings->verification_notify_assigned_user) {
            foreach (static::assignedRecipients($workItem) as $delivery) {
                $deliveries[$delivery['panel'] . ':' . $delivery['recipient']->getKey()] = $delivery;
            }
        }

        if (static::shouldNotifyClinic($workItem, $settings, $activity->activity_type)) {
            foreach (static::clinicRecipients($workItem) as $recipient) {
                $deliveries['clinic:' . $recipient->getKey()] = [
                    'panel' => 'clinic',
                    'recipient' => $recipient,
                ];
            }
        }

        foreach ($deliveries as $delivery) {
            static::storeNotification(
                $delivery['recipient'],
                $delivery['panel'],
                $payload,
                $workItem,
                $activity
            );
        }
    }

    public static function syncSlaAlertsForUser(User $user, string $panel, ?int $clinicId = null): void
    {
        $settings = SaasSetting::current();

        if (! $settings->verification_notify_on_sla_alert) {
            return;
        }

        $query = BillingWorkItem::query()
            ->with(['clinic', 'patient', 'verificationProfile', 'assignedTo'])
            ->whereNotNull('due_at')
            ->whereNotIn('status', ['done'])
            ->where('status', '!=', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)
            ->when(filled($clinicId), fn ($builder) => $builder->where('clinic_id', $clinicId));

        foreach ($query->get() as $workItem) {
            if ($panel === 'clinic' && ! static::shouldNotifyClinic($workItem, $settings, 'sla_overdue')) {
                continue;
            }

            if ($panel === 'verification' && ! static::shouldNotifyVerificationUser($user, $workItem, $settings)) {
                continue;
            }

            if ($panel === 'clinic' && $user->clinic_id !== $workItem->clinic_id) {
                continue;
            }

            $activityType = null;

            if ($workItem->due_at?->isPast()) {
                $activityType = 'sla_overdue';
            } elseif ($workItem->due_at?->isToday()) {
                $activityType = 'sla_due_today';
            }

            if (! $activityType) {
                continue;
            }

            $alreadyExists = VerificationNotification::query()
                ->where('user_id', $user->getKey())
                ->where('panel', $panel)
                ->where('billing_work_item_id', $workItem->getKey())
                ->where('activity_type', $activityType)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            $payload = static::slaPayload($workItem, $activityType);

            VerificationNotification::create([
                'user_id' => $user->getKey(),
                'organization_id' => $workItem->organization_id,
                'clinic_id' => $workItem->clinic_id,
                'billing_work_item_id' => $workItem->getKey(),
                'actor_user_id' => null,
                'panel' => $panel,
                'activity_type' => $activityType,
                'level' => $payload['level'],
                'title' => $payload['title'],
                'message' => $payload['message'],
                'target_url' => static::targetUrl($panel, $workItem, $user),
                'meta' => $payload['meta'],
            ]);
        }
    }

    protected static function adminRecipients(): Collection
    {
        return User::query()
            ->where('status', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['saas_admin', 'verification_admin', 'verification_manager']))
            ->get();
    }

    protected static function assignedRecipients(BillingWorkItem $workItem): array
    {
        $recipient = $workItem->assignedTo;

        if (! $recipient instanceof User || ! $recipient->status) {
            return [];
        }

        $panel = $recipient->canAccessVerificationWorkspace() && ! $recipient->hasAnyRole(array_keys(User::clinicRoleOptions()))
            ? 'verification'
            : 'clinic';

        return [[
            'panel' => $panel,
            'recipient' => $recipient,
        ]];
    }

    protected static function clinicRecipients(BillingWorkItem $workItem): Collection
    {
        return User::query()
            ->where('status', true)
            ->where('organization_id', $workItem->organization_id)
            ->where('clinic_id', $workItem->clinic_id)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', array_keys(User::clinicRoleOptions())))
            ->get()
            ->filter(fn (User $user): bool => $user->canAccessClinicModule('verification_requests'))
            ->values();
    }

    protected static function shouldNotifyClinic(BillingWorkItem $workItem, SaasSetting $settings, string $activityType): bool
    {
        if ($workItem->source === 'clinic_self_service') {
            return (bool) $settings->verification_notify_clinic_self_service;
        }

        if ($workItem->source === 'clinic_request' && $workItem->clinicWorkspaceEnabled()) {
            return (bool) $settings->verification_notify_clinic_workspace
                && in_array($activityType, [
                    'verification_profile_saved',
                    'clinic_verification_updated',
                    'status_changed',
                    'outcome_changed',
                    'info_requested_from_clinic',
                    'returned_for_rework',
                    'rework_completed',
                ], true);
        }

        if ($workItem->source === 'clinic_request') {
            return in_array($activityType, [
                'info_requested_from_clinic',
                'returned_for_rework',
                'rework_completed',
            ], true);
        }

        return false;
    }

    protected static function shouldNotifyVerificationUser(User $user, BillingWorkItem $workItem, SaasSetting $settings): bool
    {
        if ($settings->verification_notify_admin_all && $user->hasAnyRole(['saas_admin', 'saas_manager', 'verification_admin', 'verification_manager'])) {
            return true;
        }

        return $settings->verification_notify_assigned_user
            && (int) $workItem->assigned_to === (int) $user->getKey();
    }

    protected static function eventEnabled(string $activityType, SaasSetting $settings): bool
    {
        $settingKey = static::EVENT_SETTING_MAP[$activityType] ?? null;

        if (! $settingKey) {
            return false;
        }

        return (bool) data_get($settings, $settingKey, true);
    }

    protected static function payload(BillingWorkItemActivity $activity, BillingWorkItem $workItem): array
    {
        $patientName = $workItem->patient?->full_name
            ?? $workItem->verificationProfile?->patient_name
            ?? $workItem->title;

        $clinicName = $workItem->clinic?->clinic_name ?? 'Clinic';
        $actorName = $activity->user?->name ?? 'System';
        $level = static::LEVELS[$activity->activity_type] ?? 'info';
        $shortType = match ($activity->activity_type) {
            'urgent_priority_flagged' => 'Urgent Verification',
            'urgent_priority_assigned' => 'Urgent Assignment',
            'info_requested_from_clinic' => 'Clinic Information Requested',
            'clinic_response_received' => 'Clinic Response Received',
            'returned_for_rework' => 'Returned For Rework',
            'rework_resumed' => 'Rework Started',
            'rework_completed' => 'Rework Completed',
            default => str($activity->activity_type)->replace('_', ' ')->title()->toString(),
        };

        return [
            'title' => $shortType,
            'message' => static::activityMessage($activity, $workItem, $actorName, $patientName, $clinicName),
            'level' => $level,
            'meta' => [
                'clinic_name' => $clinicName,
                'patient_name' => $patientName,
                'reference_number' => $workItem->reference_number,
                'status' => $workItem->status,
                'priority' => $workItem->priority,
                'outcome_status' => $workItem->outcome_status,
                'activity_meta' => $activity->meta,
            ],
        ];
    }

    protected static function activityMessage(
        BillingWorkItemActivity $activity,
        BillingWorkItem $workItem,
        string $actorName,
        string $patientName,
        string $clinicName,
    ): string {
        return match ($activity->activity_type) {
            'info_requested_from_clinic' => "{$actorName} requested more information from the clinic for {$patientName} - {$clinicName}.",
            'clinic_response_received' => "{$actorName} responded to the information request for {$patientName} - {$clinicName}.",
            'returned_for_rework' => "{$actorName} returned {$patientName} - {$clinicName} for correction or rework.",
            'rework_resumed' => "{$actorName} resumed rework on {$patientName} - {$clinicName}.",
            'rework_completed' => "{$actorName} completed rework for {$patientName} - {$clinicName}.",
            default => "{$actorName}: {$activity->description} {$patientName} - {$clinicName}",
        };
    }

    protected static function slaPayload(BillingWorkItem $workItem, string $activityType): array
    {
        $patientName = $workItem->patient?->full_name
            ?? $workItem->verificationProfile?->patient_name
            ?? $workItem->title;
        $clinicName = $workItem->clinic?->clinic_name ?? 'Clinic';

        return [
            'title' => $activityType === 'sla_overdue' ? 'SLA Overdue' : 'SLA Due Today',
            'message' => $activityType === 'sla_overdue'
                ? "Verification for {$patientName} is overdue against SLA at {$clinicName}."
                : "Verification for {$patientName} is due today under SLA at {$clinicName}.",
            'level' => static::LEVELS[$activityType],
            'meta' => [
                'clinic_name' => $clinicName,
                'patient_name' => $patientName,
                'reference_number' => $workItem->reference_number,
                'status' => $workItem->status,
                'priority' => $workItem->priority,
                'outcome_status' => $workItem->outcome_status,
            ],
        ];
    }

    protected static function storeNotification(User $recipient, string $panel, array $payload, BillingWorkItem $workItem, BillingWorkItemActivity $activity): void
    {
        VerificationNotification::create([
            'user_id' => $recipient->getKey(),
            'organization_id' => $workItem->organization_id,
            'clinic_id' => $workItem->clinic_id,
            'billing_work_item_id' => $workItem->getKey(),
            'actor_user_id' => $activity->user_id,
            'panel' => $panel,
            'activity_type' => $activity->activity_type,
            'level' => $payload['level'],
            'title' => $payload['title'],
            'message' => $payload['message'],
            'target_url' => static::targetUrl($panel, $workItem, $recipient),
            'meta' => $payload['meta'],
        ]);
    }

    protected static function targetUrl(string $panel, BillingWorkItem $workItem, User $recipient): ?string
    {
        if ($panel === 'verification') {
            return VerificationWorkItemResource::getUrl('edit', ['record' => $workItem]);
        }

        if (! $recipient->canPerformClinicModuleAction('verification_requests', 'update')) {
            return VerificationRequestResource::getUrl('view', ['record' => $workItem]);
        }

        return VerificationRequestResource::getUrl('edit', ['record' => $workItem]);
    }
}
