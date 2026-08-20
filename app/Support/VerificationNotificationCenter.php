<?php

namespace App\Support;

use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemActivity;
use App\Models\SaasSetting;
use App\Models\User;
use App\Models\VerificationNotification;
use App\Models\NotificationDelivery;
use App\Models\NotificationEvent;
use App\Services\Notifications\NotificationEngine;
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
        'verification_submitted_for_qa' => 'warning',
        'verification_qa_rejected' => 'danger',
        'verification_qa_approved' => 'success',
        'verification_reopened' => 'warning',
        'clinic_correction_requested' => 'danger',
        'verification_delivered' => 'success',
        'verification_delivery_resent' => 'info',
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
        'verification_submitted_for_qa' => 'verification_notify_on_status_changed',
        'verification_qa_rejected' => 'verification_notify_on_status_changed',
        'verification_qa_approved' => 'verification_notify_on_outcome_changed',
        'verification_reopened' => 'verification_notify_on_status_changed',
        'clinic_correction_requested' => 'verification_notify_on_clinic_verification_updated',
        'verification_delivered' => 'verification_notify_on_status_changed',
        'verification_delivery_resent' => 'verification_notify_on_status_changed',
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
            foreach (static::adminRecipients($workItem) as $recipient) {
                $deliveries['verification:' . $recipient->getKey()] = [
                    'panel' => 'verification',
                    'recipient' => $recipient,
                    'user' => $recipient,
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
                    'user' => $recipient,
                ];
            }
        }

        foreach ($deliveries as &$delivery) {
            $delivery['channels'] = static::channelsFor($activity->activity_type, $settings);
            $delivery['target_url'] = static::targetUrl($delivery['panel'], $workItem, $delivery['recipient']);
        }
        unset($delivery);

        static::publishEvent(
            $workItem,
            $activity->activity_type,
            $payload,
            array_values($deliveries),
            'verification.activity.' . ($activity->public_id ?: $activity->getKey()),
            $activity,
        );
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

            if ($panel === 'clinic') {
                if ((int) $user->organization_id !== (int) $workItem->organization_id) {
                    continue;
                }

                if (filled($user->clinic_id) && (int) $user->clinic_id !== (int) $workItem->clinic_id) {
                    continue;
                }
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

            static::publishEvent(
                $workItem,
                $activityType,
                $payload,
                [[
                    'user' => $user,
                    'recipient' => $user,
                    'panel' => $panel,
                    'channels' => static::channelsFor($activityType, $settings),
                    'target_url' => static::targetUrl($panel, $workItem, $user),
                ]],
                implode('.', [
                    'verification.sla',
                    $activityType,
                    $workItem->public_id ?: $workItem->getKey(),
                    now()->toDateString(),
                ]),
            );
        }
    }

    protected static function adminRecipients(BillingWorkItem $workItem): Collection
    {
        return User::query()
            ->where('status', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['verification_admin', 'verification_manager']))
            ->get()
            ->filter(fn (User $user): bool => $user->canAccessVerificationClinic((int) $workItem->clinic_id))
            ->values();
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
            'user' => $recipient,
        ]];
    }

    protected static function clinicRecipients(BillingWorkItem $workItem): Collection
    {
        return User::query()
            ->where('status', true)
            ->where('organization_id', $workItem->organization_id)
            ->where(function ($query) use ($workItem): void {
                $query->where('clinic_id', $workItem->clinic_id)
                    ->orWhereNull('clinic_id');
            })
            ->whereHas('roles', fn ($query) => $query->whereIn('name', array_keys(User::clinicRoleOptions())))
            ->get()
            ->filter(fn (User $user): bool => $user->canAccessClinicModule('verification_requests'))
            ->values();
    }

    protected static function shouldNotifyClinic(BillingWorkItem $workItem, SaasSetting $settings, string $activityType): bool
    {
        if ($activityType === 'info_requested_from_clinic') {
            return true;
        }

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
                    'verification_qa_rejected',
                    'verification_qa_approved',
                    'verification_reopened',
                    'clinic_correction_requested',
                ], true);
        }

        if ($workItem->source === 'clinic_request') {
            return in_array($activityType, [
                'info_requested_from_clinic',
                'returned_for_rework',
                'rework_completed',
                'verification_qa_rejected',
                'verification_qa_approved',
                'verification_reopened',
                'clinic_correction_requested',
            ], true);
        }

        return false;
    }

    protected static function shouldNotifyVerificationUser(User $user, BillingWorkItem $workItem, SaasSetting $settings): bool
    {
        if ($settings->verification_notify_admin_all && $user->hasAnyRole(['verification_admin', 'verification_manager'])) {
            return $user->canAccessVerificationClinic((int) $workItem->clinic_id);
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
            'verification_submitted_for_qa' => 'Ready for Audit',
            'verification_qa_rejected' => 'Audit Returned for Correction',
            'verification_qa_approved' => 'Audit Approved',
            'verification_reopened' => 'Verification Reopened',
            'clinic_correction_requested' => 'Clinic Requested Correction',
            'verification_delivered' => 'Verification Report Available',
            'verification_delivery_resent' => 'Verification Report Resent',
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
            'verification_submitted_for_qa' => "{$actorName} sent {$patientName} - {$clinicName} to Audit.",
            'verification_qa_rejected' => "Audit returned {$patientName} - {$clinicName} for correction.",
            'verification_qa_approved' => "Audit approved the verification for {$patientName} - {$clinicName}.",
            'verification_reopened' => "{$actorName} reopened the verification for {$patientName} - {$clinicName}.",
            'clinic_correction_requested' => "{$actorName} requested a correction for {$patientName} - {$clinicName}.",
            'verification_delivered' => "The verification report for {$patientName} - {$clinicName} is available.",
            'verification_delivery_resent' => "The verification report for {$patientName} - {$clinicName} was resent.",
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

    protected static function channelsFor(string $activityType, SaasSetting $settings): array
    {
        $channels = ['in_app'];

        if (! $settings->verification_email_notifications_enabled) {
            return $channels;
        }

        $emailEnabled = match ($activityType) {
            'urgent_priority_flagged', 'urgent_priority_assigned' => $settings->verification_email_on_urgent,
            'info_requested_from_clinic', 'clinic_response_received', 'returned_for_rework', 'rework_completed' => $settings->verification_email_on_clinic_action,
            'verification_submitted_for_qa', 'verification_qa_rejected', 'verification_qa_approved', 'verification_reopened', 'clinic_correction_requested' => $settings->verification_email_on_audit,
            'sla_due_today', 'sla_overdue' => $settings->verification_email_on_sla,
            default => false,
        };

        if ($emailEnabled) {
            $channels[] = 'email';
        }

        return $channels;
    }

    protected static function publishEvent(
        BillingWorkItem $workItem,
        string $activityType,
        array $payload,
        array $recipients,
        string $idempotencyKey,
        ?BillingWorkItemActivity $activity = null,
    ): void {
        if ($recipients === []) {
            return;
        }

        $payload['external_title'] = 'Verification update requires attention';
        $payload['external_message'] = implode("\n", array_filter([
            'A verification notification requires your attention in ProDental.',
            filled($workItem->reference_number) ? 'Reference: ' . $workItem->reference_number : null,
            'Sign in to review the details securely.',
        ]));

        app(NotificationEngine::class)->publish([
            'event_type' => 'verification.' . $activityType,
            'source_type' => $activity?->getMorphClass(),
            'source_id' => $activity?->getKey(),
            'subject_type' => $workItem->getMorphClass(),
            'subject_id' => $workItem->getKey(),
            'actor_user_id' => $activity?->user_id,
            'organization_id' => $workItem->organization_id,
            'clinic_id' => $workItem->clinic_id,
            'level' => $payload['level'],
            'title' => $payload['title'],
            'message' => $payload['message'],
            'payload' => $payload,
            'idempotency_key' => $idempotencyKey,
            'occurred_at' => $activity?->created_at ?: now(),
        ], $recipients, function (
            NotificationEvent $event,
            NotificationDelivery $delivery,
            User $recipient,
            array $recipientSpec,
        ) use ($activityType, $payload, $workItem, $activity): void {
            VerificationNotification::query()->firstOrCreate(
                ['notification_delivery_id' => $delivery->getKey()],
                [
                    'notification_event_id' => $event->getKey(),
                    'user_id' => $recipient->getKey(),
                    'organization_id' => $workItem->organization_id,
                    'clinic_id' => $workItem->clinic_id,
                    'billing_work_item_id' => $workItem->getKey(),
                    'actor_user_id' => $activity?->user_id,
                    'panel' => $recipientSpec['panel'],
                    'activity_type' => $activityType,
                    'level' => $payload['level'],
                    'title' => $payload['title'],
                    'message' => $payload['message'],
                    'target_url' => $recipientSpec['target_url'] ?? null,
                    'meta' => $payload['meta'],
                ],
            );
        });
    }

    protected static function targetUrl(string $panel, BillingWorkItem $workItem, User $recipient): ?string
    {
        if ($panel === 'verification') {
            return route('filament.admin.resources.verifications.edit', ['record' => $workItem]);
        }

        if (! $recipient->canPerformClinicModuleAction('verification_requests', 'update')) {
            return route('filament.clinic.resources.verification-requests.view', ['record' => $workItem]);
        }

        return route('filament.clinic.resources.verification-requests.edit', ['record' => $workItem]);
    }
}
