<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class BillingWorkItem extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REVIEW = 'review';
    public const STATUS_AWAITING_CLINIC_RESPONSE = 'awaiting_clinic_response';
    public const STATUS_RETURNED_FOR_REWORK = 'returned_for_rework';
    public const STATUS_INCOMPLETE = 'incomplete';
    public const STATUS_DONE = 'done';

    public const STATUS_OPTIONS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_REVIEW => 'Review',
        self::STATUS_AWAITING_CLINIC_RESPONSE => 'Awaiting Clinic Response',
        self::STATUS_RETURNED_FOR_REWORK => 'Returned for Rework',
        self::STATUS_INCOMPLETE => 'Incomplete',
        self::STATUS_DONE => 'Done',
    ];

    public const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_IN_PROGRESS, self::STATUS_REVIEW, self::STATUS_AWAITING_CLINIC_RESPONSE, self::STATUS_INCOMPLETE, self::STATUS_DONE],
        self::STATUS_IN_PROGRESS => [self::STATUS_REVIEW, self::STATUS_AWAITING_CLINIC_RESPONSE, self::STATUS_INCOMPLETE, self::STATUS_DONE],
        self::STATUS_REVIEW => [self::STATUS_DONE, self::STATUS_RETURNED_FOR_REWORK, self::STATUS_AWAITING_CLINIC_RESPONSE, self::STATUS_INCOMPLETE],
        self::STATUS_AWAITING_CLINIC_RESPONSE => [self::STATUS_IN_PROGRESS, self::STATUS_INCOMPLETE],
        self::STATUS_RETURNED_FOR_REWORK => [self::STATUS_IN_PROGRESS, self::STATUS_REVIEW, self::STATUS_AWAITING_CLINIC_RESPONSE, self::STATUS_INCOMPLETE, self::STATUS_DONE],
        self::STATUS_INCOMPLETE => [self::STATUS_IN_PROGRESS, self::STATUS_REVIEW, self::STATUS_AWAITING_CLINIC_RESPONSE, self::STATUS_DONE],
        self::STATUS_DONE => [self::STATUS_RETURNED_FOR_REWORK, self::STATUS_IN_PROGRESS],
    ];

    public const OUTCOME_STATUS_OPTIONS = [
        'pending' => 'Pending',
        'verified' => 'Verified',
        'unable_to_verify' => 'Unable to Verify',
        'info_requested' => 'Info Requested',
        'audit_required' => 'Audit Required',
        'written_back' => 'Written Back',
    ];

    public const PRIORITY_OPTIONS = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public const SOURCE_OPTIONS = [
        'manual' => 'Manual',
        'clinic_request' => 'Clinic Request',
        'clinic_self_service' => 'Clinic Self-Service',
        'appointment_sync' => 'Appointment Sync',
        'claim_trigger' => 'Claim Trigger',
    ];

    public const PMS_SYNC_STATUS_OPTIONS = [
        'not_applicable' => 'Not Applicable',
        'pending' => 'Pending',
        'synced' => 'Synced',
        'failed' => 'Failed',
    ];

    public const WRITEBACK_STATUS_OPTIONS = [
        'not_requested' => 'Not Requested',
        'queued' => 'Queued',
        'written_back' => 'Written Back',
        'failed' => 'Failed',
    ];

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'managed_billing_service_id',
        'client_service_enrollment_id',
        'appointment_id',
        'patient_id',
        'provider_id',
        'patient_insurance_policy_id',
        'patient_insurance_claim_id',
        'assigned_to',
        'reviewed_by',
        'created_by',
        'returned_by_user_id',
        'returned_by_role',
        'return_reason',
        'info_requested_by_user_id',
        'info_requested_by_role',
        'info_request_reason',
        'clinic_responded_by_user_id',
        'clinic_responded_at',
        'sla_pause_started_at',
        'sla_paused_seconds',
        'reworked_by_user_id',
        'closed_by_user_id',
        'reference_number',
        'title',
        'status',
        'outcome_status',
        'priority',
        'source',
        'pms_sync_status',
        'writeback_status',
        'due_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'notes',
        'internal_summary',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'clinic_responded_at' => 'datetime',
            'sla_pause_started_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $workItem): void {
            if (blank($workItem->created_by) && auth()->check()) {
                $workItem->created_by = auth()->id();
            }

            if (blank($workItem->status)) {
                $workItem->status = self::STATUS_PENDING;
            }

            if (blank($workItem->outcome_status)) {
                $workItem->outcome_status = 'pending';
            }

            if (blank($workItem->priority)) {
                $workItem->priority = 'normal';
            }

            if (blank($workItem->reference_number)) {
                $workItem->reference_number = self::generateReferenceNumber();
            }
        });

        static::created(function (self $workItem): void {
            $workItem->recordActivity(
                'created',
                'Work item created.',
            );

            if (filled($workItem->assigned_to)) {
                $assignee = $workItem->assignedTo?->name ?? 'Unassigned';

                $workItem->recordActivity('assignment_changed', "Assigned to {$assignee}.", [
                    'assignment_mode' => 'auto_on_create',
                ]);

                if ($workItem->priority === 'urgent') {
                    $workItem->recordActivity('urgent_priority_assigned', "Urgent verification assigned to {$assignee}.", [
                        'assignment_mode' => 'auto_on_create',
                    ]);
                }
            }

            if ($workItem->priority === 'urgent') {
                $workItem->recordActivity('urgent_priority_flagged', 'Verification marked as urgent.');
            }
        });

        static::updated(function (self $workItem): void {
            $changes = $workItem->getChanges();

            if (array_key_exists('status', $changes)) {
                $workItem->recordActivity('status_changed', 'Status updated to ' . (self::STATUS_OPTIONS[$workItem->status] ?? $workItem->status) . '.');
            }

            if (array_key_exists('assigned_to', $changes)) {
                $assignee = $workItem->assignedTo?->name ?? 'Unassigned';
                $workItem->recordActivity('assignment_changed', "Assigned to {$assignee}.");

                if ($workItem->priority === 'urgent' && filled($workItem->assigned_to)) {
                    $workItem->recordActivity('urgent_priority_assigned', "Urgent verification assigned to {$assignee}.");
                }
            }

            if (array_key_exists('outcome_status', $changes) && filled($workItem->outcome_status)) {
                $workItem->recordActivity('outcome_changed', 'Outcome updated to ' . (self::OUTCOME_STATUS_OPTIONS[$workItem->outcome_status] ?? $workItem->outcome_status) . '.');
            }

            if (array_key_exists('priority', $changes) && $workItem->priority === 'urgent') {
                $workItem->recordActivity('urgent_priority_flagged', 'Verification marked as urgent.');
            }

            if (array_key_exists('status', $changes) && filled($workItem->appointment_id)) {
                $workItem->syncAppointmentVerificationStatus();
            }

            if (! array_intersect(array_keys($changes), ['status', 'assigned_to', 'outcome_status']) && count($changes) > 0) {
                $workItem->recordActivity('updated', 'Work item details updated.');
            }
        });
    }

    public static function generateReferenceNumber(): string
    {
        $prefix = 'BWI-' . now()->format('Ym') . '-';

        $latest = static::withTrashed()
            ->where('reference_number', 'like', "{$prefix}%")
            ->latest('id')
            ->value('reference_number');

        $sequence = 1;

        if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function managedBillingService(): BelongsTo
    {
        return $this->belongsTo(ManagedBillingService::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ClientServiceEnrollment::class, 'client_service_enrollment_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function insurancePolicy(): BelongsTo
    {
        return $this->belongsTo(PatientInsurancePolicy::class, 'patient_insurance_policy_id');
    }

    public function insuranceClaim(): BelongsTo
    {
        return $this->belongsTo(PatientInsuranceClaim::class, 'patient_insurance_claim_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by_user_id');
    }

    public function infoRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'info_requested_by_user_id');
    }

    public function clinicRespondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clinic_responded_by_user_id');
    }

    public function reworkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reworked_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(BillingWorkItemNote::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(BillingWorkItemAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(BillingWorkItemActivity::class);
    }

    public function verificationProfile(): HasOne
    {
        return $this->hasOne(VerificationProfile::class);
    }

    public function verificationPlanSnapshots(): HasMany
    {
        return $this->hasMany(VerificationPlanSnapshot::class);
    }

    public function verificationFormAnswers(): HasMany
    {
        return $this->hasMany(VerificationFormAnswer::class);
    }

    public function verificationCoverageCodes(): HasMany
    {
        return $this->hasMany(VerificationCoverageCode::class);
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(VerificationFormSubmission::class);
    }

    public function recordActivity(string $type, string $description, array $meta = []): void
    {
        $this->activities()->create([
            'user_id' => auth()->id(),
            'activity_type' => $type,
            'description' => $description,
            'meta' => filled($meta) ? $meta : null,
        ]);
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim(($this->reference_number ?? 'Work Item') . ' - ' . $this->title),
        );
    }

    public function clinicWorkspaceEnabled(): bool
    {
        if ($this->source !== 'clinic_request') {
            return true;
        }

        return (bool) ($this->resolveClinicVerificationEnrollment()?->clinic_workspace_enabled ?? false);
    }

    public function clinicUserCanEditVerification(?Authenticatable $user = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if (! $user->canEditClinicVerificationRequests()) {
            return false;
        }

        if ($this->source !== 'clinic_request') {
            return true;
        }

        if ($this->clinicWorkspaceEnabled() || $user->shouldBypassClinicScope()) {
            return true;
        }

        return in_array($this->normalized_status, [
            self::STATUS_AWAITING_CLINIC_RESPONSE,
            self::STATUS_REVIEW,
            self::STATUS_DONE,
        ], true);
    }

    public function verificationUserCanEditVerification(?Authenticatable $user = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if (! $user->canWorkVerificationQueue()) {
            return false;
        }

        if ($user->canManageVerificationQueue()) {
            return true;
        }

        if ((int) $this->assigned_to !== (int) $user->getAuthIdentifier()) {
            return false;
        }

        return in_array($this->normalized_status, [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_RETURNED_FOR_REWORK,
        ], true);
    }

    public function workflowMode(): string
    {
        if ($this->source === 'clinic_self_service') {
            return 'self_service';
        }

        if ($this->source === 'clinic_request' && $this->clinicWorkspaceEnabled()) {
            return 'shared_workspace';
        }

        if ($this->source === 'clinic_request') {
            return 'managed_service';
        }

        return 'verification_only';
    }

    public function isSelfServiceMode(): bool
    {
        return $this->workflowMode() === 'self_service';
    }

    public function isSharedWorkspaceMode(): bool
    {
        return $this->workflowMode() === 'shared_workspace';
    }

    public function isManagedServiceMode(): bool
    {
        return $this->workflowMode() === 'managed_service';
    }

    public function canUserTransitionTo(?User $user, string $targetStatus): bool
    {
        if (! $user?->status) {
            return false;
        }

        $targetStatus = self::normalizeStatus($targetStatus);
        $currentStatus = $this->normalized_status;

        if (! $this->canTransitionTo($targetStatus) && $currentStatus !== $targetStatus) {
            return false;
        }

        $isVerificationManager = $user->canManageVerificationQueue();
        $isVerificationUser = $user->canWorkVerificationQueue();
        $isClinicUser = $user->canEditClinicVerificationRequests();

        if ($isVerificationUser) {
            return $this->canVerificationTeamTransitionTo($targetStatus, $user, $isVerificationManager);
        }

        if ($isClinicUser) {
            return $this->canClinicTransitionTo($targetStatus);
        }

        return false;
    }

    protected function canVerificationTeamTransitionTo(string $targetStatus, User $user, bool $isManager): bool
    {
        if ($isManager) {
            return match ($this->normalized_status) {
                self::STATUS_PENDING => in_array($targetStatus, [self::STATUS_IN_PROGRESS, self::STATUS_INCOMPLETE], true),
                self::STATUS_IN_PROGRESS => in_array($targetStatus, [self::STATUS_REVIEW, self::STATUS_AWAITING_CLINIC_RESPONSE, self::STATUS_INCOMPLETE], true),
                self::STATUS_REVIEW => in_array($targetStatus, [self::STATUS_DONE, self::STATUS_RETURNED_FOR_REWORK, self::STATUS_AWAITING_CLINIC_RESPONSE, self::STATUS_INCOMPLETE], true),
                self::STATUS_AWAITING_CLINIC_RESPONSE => in_array($targetStatus, [self::STATUS_IN_PROGRESS, self::STATUS_INCOMPLETE], true),
                self::STATUS_RETURNED_FOR_REWORK => in_array($targetStatus, [self::STATUS_IN_PROGRESS, self::STATUS_REVIEW, self::STATUS_INCOMPLETE], true),
                self::STATUS_INCOMPLETE => in_array($targetStatus, [self::STATUS_IN_PROGRESS, self::STATUS_REVIEW], true),
                self::STATUS_DONE => in_array($targetStatus, [self::STATUS_RETURNED_FOR_REWORK, self::STATUS_IN_PROGRESS], true),
                default => false,
            };
        }

        return match ($this->normalized_status) {
            self::STATUS_PENDING => $targetStatus === self::STATUS_IN_PROGRESS,
            self::STATUS_IN_PROGRESS => in_array($targetStatus, [self::STATUS_REVIEW, self::STATUS_AWAITING_CLINIC_RESPONSE, self::STATUS_INCOMPLETE], true),
            self::STATUS_REVIEW => false,
            self::STATUS_AWAITING_CLINIC_RESPONSE => false,
            self::STATUS_RETURNED_FOR_REWORK => in_array($targetStatus, [self::STATUS_IN_PROGRESS, self::STATUS_REVIEW, self::STATUS_INCOMPLETE], true),
            self::STATUS_INCOMPLETE => false,
            self::STATUS_DONE => false,
            default => false,
        };
    }

    protected function canClinicTransitionTo(string $targetStatus): bool
    {
        return match ($this->workflowMode()) {
            'self_service' => match ($this->normalized_status) {
                self::STATUS_PENDING => in_array($targetStatus, [self::STATUS_IN_PROGRESS, self::STATUS_INCOMPLETE], true),
                self::STATUS_IN_PROGRESS => in_array($targetStatus, [self::STATUS_REVIEW, self::STATUS_INCOMPLETE], true),
                self::STATUS_REVIEW => in_array($targetStatus, [self::STATUS_DONE, self::STATUS_RETURNED_FOR_REWORK], true),
                self::STATUS_RETURNED_FOR_REWORK => in_array($targetStatus, [self::STATUS_IN_PROGRESS, self::STATUS_REVIEW], true),
                self::STATUS_INCOMPLETE => in_array($targetStatus, [self::STATUS_IN_PROGRESS, self::STATUS_REVIEW], true),
                self::STATUS_DONE => $targetStatus === self::STATUS_RETURNED_FOR_REWORK,
                default => false,
            },
            'shared_workspace' => match ($this->normalized_status) {
                self::STATUS_PENDING => $targetStatus === self::STATUS_IN_PROGRESS,
                self::STATUS_IN_PROGRESS => in_array($targetStatus, [self::STATUS_REVIEW, self::STATUS_INCOMPLETE], true),
                self::STATUS_AWAITING_CLINIC_RESPONSE => $targetStatus === self::STATUS_IN_PROGRESS,
                self::STATUS_RETURNED_FOR_REWORK => in_array($targetStatus, [self::STATUS_IN_PROGRESS, self::STATUS_REVIEW], true),
                self::STATUS_REVIEW, self::STATUS_DONE => $targetStatus === self::STATUS_RETURNED_FOR_REWORK,
                self::STATUS_INCOMPLETE => $targetStatus === self::STATUS_IN_PROGRESS,
                default => false,
            },
            'managed_service' => match ($this->normalized_status) {
                self::STATUS_AWAITING_CLINIC_RESPONSE => $targetStatus === self::STATUS_IN_PROGRESS,
                self::STATUS_REVIEW, self::STATUS_DONE => $targetStatus === self::STATUS_RETURNED_FOR_REWORK,
                default => false,
            },
            default => false,
        };
    }

    protected function resolveClinicVerificationEnrollment(): ?ClientServiceEnrollment
    {
        if ($this->enrollment) {
            return $this->enrollment;
        }

        if ($this->source !== 'clinic_request' || blank($this->organization_id) || blank($this->clinic_id)) {
            return null;
        }

        return ClientServiceEnrollment::query()
            ->where('organization_id', $this->organization_id)
            ->where('clinic_id', $this->clinic_id)
            ->where('status', 'active')
            ->when(
                filled($this->location_id),
                fn ($query) => $query->where(function ($innerQuery): void {
                    $innerQuery->whereNull('location_id')->orWhere('location_id', $this->location_id);
                }),
                fn ($query) => $query->whereNull('location_id'),
            )
            ->whereHas('managedBillingService', fn ($query) => $query->where('category', 'verification'))
            ->orderByRaw('case when location_id is null then 1 else 0 end')
            ->first();
    }

    protected function slaStatus(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (! $this->due_at) {
                    return 'not_set';
                }

                if ($this->normalized_status === self::STATUS_DONE) {
                    return 'closed';
                }

                if ($this->normalized_status === self::STATUS_AWAITING_CLINIC_RESPONSE) {
                    return 'paused_waiting_clinic';
                }

                if ($this->due_at->isPast()) {
                    return 'overdue';
                }

                if ($this->due_at->isToday()) {
                    return 'due_today';
                }

                return 'on_track';
            },
        );
    }

    protected function normalizedStatus(): Attribute
    {
        return Attribute::make(
            get: fn (): string => self::normalizeStatus($this->status),
        );
    }

    public static function normalizeStatus(?string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_REVIEW,
            self::STATUS_AWAITING_CLINIC_RESPONSE,
            self::STATUS_RETURNED_FOR_REWORK,
            self::STATUS_INCOMPLETE,
            self::STATUS_DONE => $status,
            'unassigned', 'assigned' => self::STATUS_PENDING,
            'incomplete', 'waiting_on_payer' => self::STATUS_IN_PROGRESS,
            'waiting_on_client' => self::STATUS_AWAITING_CLINIC_RESPONSE,
            'audit' => self::STATUS_RETURNED_FOR_REWORK,
            'ready_for_review' => self::STATUS_REVIEW,
            'completed', 'cancelled' => self::STATUS_DONE,
            default => self::STATUS_PENDING,
        };
    }

    public function canTransitionTo(string $status): bool
    {
        $current = $this->normalized_status;

        return in_array($status, self::STATUS_TRANSITIONS[$current] ?? [], true);
    }

    public function startWork(?int $userId = null): void
    {
        if (blank($this->assigned_to) && filled($userId)) {
            $this->assigned_to = $userId;
        }

        if ($this->normalized_status === self::STATUS_PENDING) {
            $this->status = self::STATUS_IN_PROGRESS;
        } else {
            $this->status = $this->normalized_status;
        }

        if (blank($this->started_at)) {
            $this->started_at = now();
        }

        $this->save();
    }

    public function transitionStatus(string $status): void
    {
        $currentStatus = $this->normalized_status;
        $normalizedTarget = self::normalizeStatus($status);
        $actorRole = static::resolveActorRole();
        $activityEvents = [];

        if (! $this->canTransitionTo($normalizedTarget) && $currentStatus !== $normalizedTarget) {
            return;
        }

        $this->status = $normalizedTarget;

        if ($normalizedTarget === self::STATUS_IN_PROGRESS && blank($this->started_at)) {
            $this->started_at = now();
        }

        if ($normalizedTarget === self::STATUS_AWAITING_CLINIC_RESPONSE) {
            $this->info_requested_by_user_id ??= auth()->id();
            $this->info_requested_by_role ??= $actorRole;

            if ($this->due_at && blank($this->sla_pause_started_at)) {
                $this->sla_pause_started_at = now();
            }

            $activityEvents[] = [
                'type' => 'info_requested_from_clinic',
                'description' => 'Verification requested additional information from the clinic.',
                'meta' => [
                    'info_request_reason' => $this->info_request_reason,
                    'requested_by_role' => $actorRole,
                ],
            ];
        }

        if ($normalizedTarget === self::STATUS_RETURNED_FOR_REWORK) {
            $this->returned_by_user_id ??= auth()->id();
            $this->returned_by_role ??= $actorRole;

            $activityEvents[] = [
                'type' => 'returned_for_rework',
                'description' => 'Request was sent back for rework.',
                'meta' => [
                    'return_reason' => $this->return_reason,
                    'returned_by_role' => $actorRole,
                ],
            ];
        }

        if ($currentStatus === self::STATUS_AWAITING_CLINIC_RESPONSE && $normalizedTarget !== self::STATUS_AWAITING_CLINIC_RESPONSE) {
            if ($this->due_at && filled($this->sla_pause_started_at)) {
                $pausedSeconds = max(0, $this->sla_pause_started_at->diffInSeconds(now()));
                $this->sla_paused_seconds = (int) ($this->sla_paused_seconds ?? 0) + $pausedSeconds;
                $this->due_at = $this->due_at->copy()->addSeconds($pausedSeconds);
            }

            $this->sla_pause_started_at = null;

            if ($this->outcome_status === 'info_requested') {
                $this->outcome_status = 'pending';
            }
        }

        if ($currentStatus === self::STATUS_AWAITING_CLINIC_RESPONSE && $normalizedTarget === self::STATUS_IN_PROGRESS) {
            $this->clinic_responded_at = now();

            if (auth()->check()) {
                $this->clinic_responded_by_user_id = auth()->id();
            }

            $activityEvents[] = [
                'type' => 'clinic_response_received',
                'description' => 'Clinic responded and verification resumed.',
                'meta' => [
                    'clinic_response_note' => $this->notes,
                    'responded_by_role' => $actorRole,
                ],
            ];
        }

        if ($currentStatus === self::STATUS_RETURNED_FOR_REWORK && in_array($normalizedTarget, [self::STATUS_IN_PROGRESS, self::STATUS_REVIEW], true) && auth()->check()) {
            $this->reworked_by_user_id = auth()->id();

            $activityEvents[] = [
                'type' => $normalizedTarget === self::STATUS_REVIEW ? 'rework_completed' : 'rework_resumed',
                'description' => $normalizedTarget === self::STATUS_REVIEW
                    ? 'Rework was completed and returned to review.'
                    : 'Rework started and verification resumed.',
                'meta' => [
                    'reworked_by_role' => $actorRole,
                ],
            ];
        }

        if ($normalizedTarget === self::STATUS_DONE) {
            $this->completed_at = now();
            if (auth()->check()) {
                $this->closed_by_user_id = auth()->id();
            }
        } elseif ($normalizedTarget !== self::STATUS_DONE) {
            $this->completed_at = null;
        }

        $this->save();

        foreach ($activityEvents as $event) {
            $this->recordActivity($event['type'], $event['description'], $event['meta'] ?? []);
        }
    }

    protected static function resolveActorRole(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return 'admin';
        }

        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole(['owner', 'super_admin', 'admin'])) {
                return 'admin';
            }

            if ($user->hasAnyRole(['revenue_manager', 'operations_manager', 'manager'])) {
                return 'verification_manager';
            }

            if ($user->hasAnyRole(['clinic_admin', 'clinic_manager', 'front_desk', 'scheduler'])) {
                return 'clinic';
            }
        }

        if (filled($user->clinic_id)) {
            return 'clinic';
        }

        return 'verification_user';
    }

    protected function syncAppointmentVerificationStatus(): void
    {
        $appointment = $this->appointment;

        if (! $appointment) {
            return;
        }

        $status = match ($this->normalized_status) {
            self::STATUS_DONE => Appointment::VERIFICATION_STATUS_COMPLETED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_REVIEW,
            self::STATUS_AWAITING_CLINIC_RESPONSE,
            self::STATUS_RETURNED_FOR_REWORK,
            self::STATUS_INCOMPLETE => Appointment::VERIFICATION_STATUS_IN_PROGRESS,
            default => Appointment::VERIFICATION_STATUS_SENT,
        };

        $appointment->forceFill([
            'verification_status' => $status,
            'verification_work_item_id' => $this->getKey(),
        ])->save();
    }
}
