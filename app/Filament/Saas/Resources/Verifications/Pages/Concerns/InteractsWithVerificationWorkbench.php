<?php

namespace App\Filament\Saas\Resources\Verifications\Pages\Concerns;

use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemActivity;
use App\Models\BillingWorkItemAttachment;
use App\Models\VerificationFormSubmission;
use App\Services\Verification\WorkflowService;
use Illuminate\Support\Collection;

trait InteractsWithVerificationWorkbench
{
    public bool $showSubmissionSnapshotModal = false;

    public ?array $selectedSubmissionSnapshot = null;

    public function getWorkflowLifecycle(): array
    {
        /** @var BillingWorkItem $record */
        $record = $this->getRecord();

        $snapshot = app(WorkflowService::class)->lifecycleSnapshot($record);
        $active = collect($snapshot)->firstWhere('active', true);

        return [
            'active_label' => $active['label'] ?? 'Request',
            'active_key' => $active['key'] ?? 'request',
            'items' => $snapshot,
        ];
    }

    public function getVerificationPanels(): array
    {
        /** @var BillingWorkItem $record */
        $record = $this->getRecord();
        $profile = $record->verificationProfile;
        $codeRows = method_exists($this, 'getCodeCoverageSection') ? $this->getCodeCoverageSection() : null;

        return [
            [
                'title' => 'Request Snapshot',
                'items' => [
                    ['label' => 'Patient name', 'value' => $profile?->patient_full_name ?: ($record->patient?->full_name ?: '-')],
                    ['label' => 'Date of birth', 'value' => optional($profile?->patient_dob)->format('m-d-Y') ?: (optional($record->patient?->dob)->format('m-d-Y') ?: '-')],
                    ['label' => 'Member ID', 'value' => $profile?->patient_identifier ?: ($record->insurancePolicy?->member_id ?: '-')],
                    ['label' => 'Form type', 'value' => \App\Models\VerificationProfile::FORM_TYPE_OPTIONS[$profile?->form_type ?? 'full_form'] ?? 'Full Form'],
                    ['label' => 'Requested by', 'value' => $profile?->requested_by_name ?: '-'],
                    ['label' => 'Requested from', 'value' => filled($profile?->requested_from_panel) ? str($profile->requested_from_panel)->headline()->toString() : '-'],
                ],
            ],
            [
                'title' => 'Insurance Snapshot',
                'items' => [
                    ['label' => 'Insurance provider', 'value' => $profile?->insurance_provider_name ?: ($record->insurancePolicy?->insurance_company ?: '-')],
                    ['label' => 'Payer ID', 'value' => $profile?->payer_id ?: '-'],
                    ['label' => 'Group name', 'value' => $profile?->group_name ?: '-'],
                    ['label' => 'Group number', 'value' => $profile?->group_number ?: '-'],
                    ['label' => 'Effective date', 'value' => optional($profile?->effective_date)->format('m-d-Y') ?: '-'],
                    ['label' => 'Network status', 'value' => $profile?->network_status ?: '-'],
                ],
            ],
            [
                'title' => 'Benefits Snapshot',
                'items' => [
                    ['label' => 'Annual maximum', 'value' => $this->money($profile?->annual_maximum)],
                    ['label' => 'Remaining maximum', 'value' => $this->money($profile?->annual_maximum_remaining)],
                    ['label' => 'Individual deductible', 'value' => $this->money($profile?->individual_deductible)],
                    ['label' => 'Deductible remaining', 'value' => $this->money($profile?->individual_deductible_remaining)],
                    ['label' => 'Family deductible', 'value' => $this->money($profile?->family_deductible)],
                    ['label' => 'Family deductible remaining', 'value' => $this->money($profile?->family_deductible_remaining)],
                ],
            ],
            [
                'title' => 'Verification Notes',
                'notes' => [
                    'label' => 'Verification notes',
                    'value' => collect([
                        $profile?->verification_notes,
                        filled($record->notes) ? "Queue Notes:\n{$record->notes}" : null,
                        filled($record->internal_summary) ? "Internal Summary:\n{$record->internal_summary}" : null,
                    ])->filter()->implode("\n\n") ?: 'No verification notes have been entered yet.',
                ],
            ],
        ];
    }

    public function getAuthoritativeSubmissionSnapshot(): ?array
    {
        /** @var BillingWorkItem $record */
        $record = $this->getRecord();

        if ($record->normalized_status !== BillingWorkItem::STATUS_DONE) {
            return null;
        }

        $submission = $record->formSubmissions()
            ->with('user')
            ->latest('version')
            ->first();

        return $submission ? $this->formatSubmissionSnapshot($submission) : null;
    }

    public function getWorkbenchSummary(): array
    {
        /** @var BillingWorkItem $record */
        $record = $this->getRecord();

        return [
            [
                'label' => 'Work status',
                'value' => BillingWorkItem::STATUS_OPTIONS[$record->normalized_status] ?? str($record->normalized_status)->headline()->toString(),
                'tone' => $this->resolveTone($record->normalized_status, [
                    BillingWorkItem::STATUS_DONE => 'emerald',
                    BillingWorkItem::STATUS_REVIEW => 'sky',
                    BillingWorkItem::STATUS_RETURNED_FOR_REWORK => 'rose',
                    BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE => 'amber',
                    BillingWorkItem::STATUS_PENDING => 'amber',
                    BillingWorkItem::STATUS_IN_PROGRESS => 'sky',
                    BillingWorkItem::STATUS_INCOMPLETE => 'slate',
                ]),
            ],
            [
                'label' => 'Verification result',
                'value' => BillingWorkItem::OUTCOME_STATUS_OPTIONS[$record->outcome_status] ?? str($record->outcome_status)->headline()->toString(),
                'tone' => $this->resolveTone($record->outcome_status, [
                    'verified' => 'emerald',
                    'written_back' => 'sky',
                    'unable_to_verify' => 'rose',
                    'info_requested' => 'amber',
                    'audit_required' => 'violet',
                    'pending' => 'slate',
                ]),
            ],
            [
                'label' => 'PMS sync',
                'value' => BillingWorkItem::PMS_SYNC_STATUS_OPTIONS[$record->pms_sync_status] ?? str($record->pms_sync_status)->headline()->toString(),
                'tone' => $this->resolveTone($record->pms_sync_status, [
                    'synced' => 'emerald',
                    'failed' => 'rose',
                    'pending' => 'amber',
                    'not_applicable' => 'slate',
                ]),
            ],
            [
                'label' => 'Priority',
                'value' => BillingWorkItem::PRIORITY_OPTIONS[$record->priority] ?? str($record->priority)->headline()->toString(),
                'tone' => $this->resolveTone($record->priority, [
                    'urgent' => 'rose',
                    'high' => 'amber',
                    'normal' => 'sky',
                    'low' => 'slate',
                ]),
            ],
        ];
    }

    public function getContextRows(): array
    {
        /** @var BillingWorkItem $record */
        $record = $this->getRecord();

        return [
            'practice' => [
                ['label' => 'Organization', 'value' => $record->organization?->name ?: '-'],
                ['label' => 'Clinic', 'value' => $record->clinic?->clinic_name ?: '-'],
                ['label' => 'Location', 'value' => $record->location?->location_name ?: ($record->verificationProfile?->location_name ?: '-')],
                ['label' => 'Enrollment', 'value' => $record->enrollment?->display_title ?: '-'],
            ],
            'patient' => [
                ['label' => 'Patient', 'value' => $record->verificationProfile?->patient_full_name ?: ($record->patient?->full_name ?: '-')],
                ['label' => 'DOB', 'value' => optional($record->verificationProfile?->patient_dob)->format('m-d-Y') ?: (optional($record->patient?->dob)->format('m-d-Y') ?: '-')],
                ['label' => 'Member ID', 'value' => $record->verificationProfile?->patient_identifier ?: ($record->insurancePolicy?->member_id ?: '-')],
                ['label' => 'Subscriber', 'value' => $record->verificationProfile?->subscriber_name ?: ($record->insurancePolicy?->subscriber_name ?: '-')],
            ],
            'coverage' => [
                ['label' => 'Insurance', 'value' => $record->insurancePolicy?->insurance_company ?: ($record->verificationProfile?->insurance_provider_name ?: '-')],
                ['label' => 'Payer ID', 'value' => $record->verificationProfile?->payer_id ?: '-'],
                ['label' => 'Group #', 'value' => $record->verificationProfile?->group_number ?: '-'],
                ['label' => 'Network', 'value' => $record->verificationProfile?->network_status ?: '-'],
            ],
            'appointment' => [
                ['label' => 'Appointment date', 'value' => optional($record->appointment?->appointment_date)->format('m-d-Y') ?: (optional($record->verificationProfile?->appointment_date)->format('m-d-Y') ?: '-')],
                ['label' => 'Appointment time', 'value' => $record->verificationProfile?->appointment_time ?: ($record->appointment?->start_time ?: '-')],
                ['label' => 'Provider', 'value' => $record->provider?->display_name ?: ($record->verificationProfile?->provider_name ?: '-')],
                ['label' => 'Assigned to', 'value' => $record->assignedTo?->name ?: 'Queue'],
                ['label' => 'PMS ID', 'value' => $record->verificationProfile?->pms_id ?: '-'],
            ],
        ];
    }

    public function getVerificationSectionProgress(): array
    {
        /** @var BillingWorkItem $record */
        $record = $this->getRecord();
        $profile = $record->verificationProfile;
        $codeRows = method_exists($this, 'getCodeCoverageSection') ? $this->getCodeCoverageSection() : null;

        return [
            [
                'label' => 'Patient Information',
                'completed' => $this->countCompleted([
                    $profile?->patient_full_name,
                    $profile?->patient_dob,
                    $profile?->patient_identifier,
                    $profile?->patient_zip,
                    $profile?->pms_id,
                ]),
                'total' => 5,
            ],
            [
                'label' => 'Insurance Information',
                'completed' => $this->countCompleted([
                    $profile?->insurance_provider_name,
                    $profile?->insurance_company_phone_number,
                    $profile?->payer_id,
                    $profile?->effective_date,
                    $profile?->group_name,
                    $profile?->group_number,
                    $profile?->network_status,
                    $profile?->fee_schedule,
                    $profile?->plan_type,
                ]),
                'total' => 9,
            ],
            [
                'label' => 'Maximums & Deductibles',
                'completed' => $this->countCompleted([
                    $profile?->annual_maximum,
                    $profile?->annual_maximum_remaining,
                    $profile?->individual_deductible,
                    $profile?->individual_deductible_remaining,
                    $profile?->family_deductible,
                    $profile?->family_deductible_remaining,
                ]),
                'total' => 6,
            ],
            [
                'label' => 'Verification Notes',
                'completed' => $this->countCompleted([
                    $profile?->verification_notes,
                    $profile?->quick_reference,
                    $record->internal_summary,
                    $record->notes,
                ]),
                'total' => 4,
            ],
            [
                'label' => 'Ortho Information',
                'completed' => $this->countCompleted([
                    $profile?->ortho_information,
                ]),
                'total' => 1,
            ],
            [
                'label' => 'Service History',
                'completed' => $this->countCompleted([
                    $profile?->service_history,
                ]),
                'total' => 1,
            ],
            [
                'label' => 'Codes & Coverage',
                'completed' => $codeRows
                    ? (int) $codeRows['completed']
                    : $this->countCompleted([
                        $profile?->coverage_diagnostic,
                        $profile?->coverage_preventive,
                        $profile?->coverage_basic_restorative,
                        $profile?->coverage_endodontics,
                        $profile?->coverage_periodontics,
                        $profile?->coverage_oral_surgery,
                        $profile?->coverage_major_restorative,
                        $profile?->coverage_prosthodontics,
                        $profile?->coverage_implant,
                    ]),
                'total' => $codeRows ? (int) $codeRows['total'] : 9,
            ],
            [
                'label' => 'Queue State',
                'completed' => $this->countCompleted([
                    $record->status,
                    $record->outcome_status,
                    $record->pms_sync_status,
                ]),
                'total' => 3,
            ],
        ];
    }

    public function getQuickReferenceCard(): array
    {
        /** @var BillingWorkItem $record */
        $record = $this->getRecord();
        $profile = $record->verificationProfile;
        $data = is_array($this->data ?? null) ? $this->data : [];
        $primaryPlan = $record->verificationPlanSnapshots
            ->sortBy(fn ($plan) => array_search($plan->plan_priority, ['primary', 'secondary', 'tertiary'], true))
            ->first();

        $patientName = $this->firstFilled(
            data_get($data, 'vf_patient_full_name'),
            $profile?->patient_full_name,
            $record->patient?->full_name
        );
        $patientDob = $this->firstFilled(
            data_get($data, 'vf_patient_dob'),
            $profile?->patient_dob,
            $record->patient?->dob
        );
        $memberId = $this->firstFilled(
            data_get($data, 'vf_patient_identifier'),
            data_get($data, 'vf_subscriber_id'),
            $profile?->patient_identifier,
            $profile?->subscriber_id,
            $record->insurancePolicy?->member_id,
            $primaryPlan?->member_id,
            $record->patient?->insurance_number
        );
        $subscriberName = $this->firstFilled(
            data_get($data, 'vf_subscriber_name'),
            $profile?->subscriber_name,
            $record->insurancePolicy?->subscriber_name,
            $primaryPlan?->subscriber_name
        );
        $subscriberDob = $this->firstFilled(
            data_get($data, 'vf_subscriber_dob'),
            $profile?->subscriber_dob,
            $record->insurancePolicy?->subscriber_dob,
            $primaryPlan?->subscriber_dob
        );
        $insuredRelation = $this->firstFilled(
            data_get($data, 'vf_insured_relation'),
            $profile?->insured_relation,
            $record->insurancePolicy?->subscriber_relationship
        );
        $isSelfRelationship = strtolower(trim((string) $insuredRelation)) === 'self';

        if ($isSelfRelationship) {
            $subscriberName = $patientName;
            $subscriberDob = $patientDob;
        }

        $providerName = $this->firstFilled(
            data_get($data, 'vf_provider_name'),
            $record->provider?->display_name,
            $profile?->provider_name
        );
        $insuranceName = $this->firstFilled(
            data_get($data, 'vf_insurance_provider_name'),
            $profile?->insurance_provider_name,
            $record->insurancePolicy?->insurance_company,
            $primaryPlan?->payer_name,
            $record->patient?->insurance_provider
        );
        $insurancePhone = $this->firstFilled(
            data_get($data, 'vf_insurance_company_phone_number'),
            $profile?->insurance_company_phone_number,
            $record->insurancePolicy?->payer_phone
        );
        $groupNumber = $this->firstFilled(
            data_get($data, 'vf_group_number'),
            $profile?->group_number,
            $record->insurancePolicy?->group_number,
            $primaryPlan?->group_number
        );
        $appointmentDate = $this->firstFilled(
            data_get($data, 'vf_appointment_date'),
            $profile?->appointment_date,
            $record->appointment?->appointment_date
        );

        return [
            'patient' => $patientName ?: '-',
            'dob' => $this->formatQuickReferenceDate($patientDob),
            'member_id' => $memberId ?: '-',
            'relationship' => filled($insuredRelation) ? str((string) $insuredRelation)->headline()->toString() : '-',
            'subscriber_name' => $subscriberName ?: '-',
            'subscriber_dob' => $this->formatQuickReferenceDate($subscriberDob),
            'insurance_name' => $insuranceName ?: '-',
            'coverage_role' => $this->resolveCoverageRole($patientName, $subscriberName, $insuredRelation),
            'group_number' => $groupNumber ?: '-',
            'appointment_date' => $this->formatQuickReferenceDate($appointmentDate),
            'provider_name' => $providerName ?: '-',
            'provider_npi' => $record->provider?->npi_number ?: '-',
            'phone' => $insurancePhone ?: '-',
        ];
    }

    public function getClinicCommunication(): array
    {
        /** @var BillingWorkItem $record */
        $record = $this->getRecord();
        $activities = $record->activities()
            ->with('user')
            ->whereIn('activity_type', [
                'info_requested_from_clinic',
                'clinic_response_received',
            ])
            ->oldest('created_at')
            ->get();

        $requests = $activities->where('activity_type', 'info_requested_from_clinic')->values();
        $responses = $activities->where('activity_type', 'clinic_response_received')->values();
        $latestRequest = $requests->last();
        $latestResponse = $responses
            ->when($latestRequest, fn (Collection $items): Collection => $items
                ->filter(fn (BillingWorkItemActivity $activity): bool => optional($activity->created_at)?->greaterThanOrEqualTo($latestRequest->created_at) ?? false)
                ->values())
            ->last();

        return [
            'request_count' => $requests->count(),
            'response_count' => $responses->count(),
            'has_activity' => $activities->isNotEmpty(),
            'waiting_for_clinic' => $record->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
            'request' => $latestRequest ? [
                'message' => trim((string) data_get($latestRequest->meta, 'info_request_reason', $latestRequest->description)) ?: 'Additional information requested.',
                'actor' => $latestRequest->user?->name ?: 'Verification Team',
                'date' => optional($latestRequest->created_at)->format('M d, Y h:i A') ?: '-',
            ] : null,
            'response' => $latestResponse ? [
                'message' => trim((string) data_get($latestResponse->meta, 'clinic_response_note', $latestResponse->description)) ?: 'Clinic response received.',
                'actor' => $latestResponse->user?->name ?: 'Clinic',
                'date' => optional($latestResponse->created_at)->format('M d, Y h:i A') ?: '-',
            ] : null,
        ];
    }

    protected function firstFilled(mixed ...$values): mixed
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    protected function formatQuickReferenceDate(mixed $value): string
    {
        if (! filled($value)) {
            return '-';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('m-d-Y');
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? (string) $value : date('m-d-Y', $timestamp);
    }

    public function getPlanSnapshots(): Collection
    {
        /** @var BillingWorkItem $record */
        $record = $this->getRecord();

        return $record->verificationPlanSnapshots
            ->sortBy(fn ($plan) => array_search($plan->plan_priority, ['primary', 'secondary', 'tertiary'], true))
            ->values()
            ->map(fn ($plan): array => [
                'priority' => \App\Models\VerificationPlanSnapshot::PRIORITY_OPTIONS[$plan->plan_priority] ?? str($plan->plan_priority)->title()->toString(),
                'payer_name' => $plan->payer_name ?: '-',
                'member_id' => $plan->member_id ?: '-',
                'group_number' => $plan->group_number ?: '-',
                'subscriber_name' => $plan->subscriber_name ?: '-',
                'subscriber_dob' => optional($plan->subscriber_dob)->format('m/d/Y') ?: '-',
                'notes' => $plan->notes,
            ]);
    }

    public function getClientVisibleNotes(): Collection
    {
        /** @var BillingWorkItem $record */
        $record = $this->getRecord();

        return $record->notes()
            ->latest('created_at')
            ->get()
            ->values();
    }

    public function getAttachmentCards(): Collection
    {
        /** @var BillingWorkItem $record */
        $record = $this->getRecord();

        return $record->attachments()
            ->latest('created_at')
            ->get()
            ->values()
            ->map(fn (BillingWorkItemAttachment $attachment): array => [
                'title' => $attachment->title ?: $attachment->original_file_name,
                'subtitle' => collect([
                    $attachment->original_file_name,
                    $attachment->mime_type,
                    filled($attachment->file_size) ? number_format(((int) $attachment->file_size) / 1024, 1) . ' KB' : null,
                ])->filter()->implode(' · '),
                'download_url' => $this->getAttachmentDownloadUrl($attachment),
                'uploaded_at' => optional($attachment->created_at)->format('M d, Y h:i A') ?: '-',
            ]);
    }

    public function getAttachmentDownloadUrl(BillingWorkItemAttachment $attachment): string
    {
        return route('admin.verification-request-attachments.download', $attachment);
    }

    public function getActivityTimeline(?int $limit = null): Collection
    {
        /** @var BillingWorkItem $record */
        $record = $this->getRecord();

        $activities = $record->activities()
            ->latest('created_at')
            ->when(filled($limit), fn ($builder) => $builder->limit($limit))
            ->get()
            ->values()
            ->map(function (BillingWorkItemActivity $activity): array {
                $details = match ($activity->activity_type) {
                    'info_requested_from_clinic' => data_get($activity->meta, 'info_request_reason'),
                    'clinic_response_received' => data_get($activity->meta, 'clinic_response_note'),
                    'clinic_correction_requested' => collect([
                        filled(data_get($activity->meta, 'reason')) ? 'Reason: ' . data_get($activity->meta, 'reason') : null,
                        collect(data_get($activity->meta, 'requested_fields', []))->isNotEmpty()
                            ? "Requested items:\n- " . collect(data_get($activity->meta, 'requested_fields', []))->values()->implode("\n- ")
                            : null,
                        filled(data_get($activity->meta, 'baseline_submission_version'))
                            ? 'Original completed result: v' . data_get($activity->meta, 'baseline_submission_version')
                            : null,
                    ])->filter()->implode("\n"),
                    'returned_for_rework' => data_get($activity->meta, 'return_reason'),
                    'attachment_added' => $this->buildAttachmentDetails($activity),
                    'attachment_downloaded' => $this->buildAttachmentDownloadDetails($activity),
                    'submission_snapshot_viewed' => $this->buildSnapshotViewDetails($activity),
                    'verification_detail_viewed' => $this->buildAccessDetails($activity),
                    'verification_console_opened' => $this->buildAccessDetails($activity),
                    'verification_delivered', 'verification_delivery_resent' => $this->buildDeliveryDetails($activity),
                    'verification_pdf_downloaded', 'verification_pdf_previewed' => $this->buildPdfAccessDetails($activity),
                    'form_submitted' => $this->buildSubmissionDetails($activity),
                    'verification_qa_approved' => filled(data_get($activity->meta, 'submission_version'))
                        ? 'Completed result: v' . data_get($activity->meta, 'submission_version') . "\nReviewed by: " . (data_get($activity->meta, 'reviewed_by') ?: 'Audit reviewer')
                        : null,
                    default => null,
                };

                return [
                    'type' => match ($activity->activity_type) {
                        'info_requested_from_clinic' => 'Information Requested',
                        'clinic_response_received' => 'Clinic Responded',
                        'clinic_correction_requested' => 'Clinic Requested Correction',
                        'returned_for_rework' => 'Returned for Rework',
                        'rework_resumed' => 'Rework Started',
                        'rework_completed' => 'Rework Completed',
                        'attachment_added' => 'Attachment Uploaded',
                        'attachment_downloaded' => 'Attachment Downloaded',
                        'submission_snapshot_viewed' => 'Snapshot Viewed',
                        'verification_detail_viewed' => 'Detail View Opened',
                        'verification_console_opened' => 'Verification Console Opened',
                        'verification_delivered' => 'Report Delivered',
                        'verification_delivery_resent' => 'Report Delivery Resent',
                        'verification_pdf_downloaded' => 'PDF Downloaded',
                        'verification_pdf_previewed' => 'PDF Previewed',
                        'form_submitted' => 'Form Submitted',
                        default => str($activity->activity_type)->replace('_', ' ')->title()->toString(),
                    },
                    'description' => $activity->description,
                    'details' => $details,
                    'author' => $activity->user?->name ?: 'System',
                    'created_at' => optional($activity->created_at)->format('M d, Y h:i A') ?: '-',
                    'created_at_raw' => $activity->created_at,
                    'tone' => match ($activity->activity_type) {
                        'info_requested_from_clinic' => 'amber',
                        'clinic_response_received', 'rework_resumed' => 'sky',
                        'clinic_correction_requested' => 'rose',
                        'returned_for_rework' => 'rose',
                        'rework_completed' => 'emerald',
                        'attachment_added' => 'violet',
                        'attachment_downloaded' => 'slate',
                        'submission_snapshot_viewed' => 'slate',
                        'verification_detail_viewed' => 'slate',
                        'verification_console_opened' => 'slate',
                        'verification_delivered' => 'emerald',
                        'verification_delivery_resent' => 'sky',
                        'verification_pdf_downloaded', 'verification_pdf_previewed' => 'slate',
                        'form_submitted' => 'indigo',
                        default => 'cyan',
                    },
                    'submission_id' => in_array($activity->activity_type, ['form_submitted', 'verification_qa_approved'], true)
                        ? data_get($activity->meta, 'submission_id')
                        : null,
                ];
            });

        return $this->injectStaleTimelineEvents($activities, $record);
    }

    public function canViewSubmissionSnapshots(): bool
    {
        if (method_exists($this, 'canManageQueueControl') && $this->canManageQueueControl()) {
            return true;
        }

        $user = auth()->user();

        return (bool) ($user?->canAccessSaasRevenueOperations() || $user?->isSaasAdmin());
    }

    public function openSubmissionSnapshot(int $submissionId): void
    {
        abort_unless($this->canViewSubmissionSnapshots(), 403);

        /** @var BillingWorkItem $record */
        $record = $this->getRecord();

        $submission = $record->formSubmissions()
            ->with('user')
            ->findOrFail($submissionId);

        $record->recordActivity('submission_snapshot_viewed', 'A saved verification snapshot was opened for audit review.', [
            'panel' => method_exists($this, 'getSubmissionPanel') ? $this->getSubmissionPanel() : 'verification',
            'submission_id' => $submission->getKey(),
            'submission_version' => $submission->version,
            'user_name' => auth()->user()?->name,
        ]);

        $this->selectedSubmissionSnapshot = $this->formatSubmissionSnapshot($submission);
        $this->showSubmissionSnapshotModal = true;
    }

    public function closeSubmissionSnapshot(): void
    {
        $this->showSubmissionSnapshotModal = false;
        $this->selectedSubmissionSnapshot = null;
    }

    protected function buildDeliveryDetails(BillingWorkItemActivity $activity): ?string
    {
        return collect([
            filled(data_get($activity->meta, 'channel'))
                ? 'Channel: ' . str((string) data_get($activity->meta, 'channel'))->replace('_', ' ')->headline()->toString()
                : null,
            filled(data_get($activity->meta, 'user_name')) ? 'Recorded by: ' . data_get($activity->meta, 'user_name') : null,
        ])->filter()->implode("\n") ?: null;
    }

    protected function buildPdfAccessDetails(BillingWorkItemActivity $activity): ?string
    {
        return collect([
            filled(data_get($activity->meta, 'panel'))
                ? 'Panel: ' . str((string) data_get($activity->meta, 'panel'))->headline()->toString()
                : null,
            filled(data_get($activity->meta, 'output_mode'))
                ? 'Output mode: ' . str((string) data_get($activity->meta, 'output_mode'))->replace('_', ' ')->headline()->toString()
                : null,
            filled(data_get($activity->meta, 'user_name')) ? 'User: ' . data_get($activity->meta, 'user_name') : null,
        ])->filter()->implode("\n") ?: null;
    }

    protected function buildSubmissionDetails(BillingWorkItemActivity $activity): ?string
    {
        $panel = match (data_get($activity->meta, 'panel')) {
            'clinic' => 'Clinic Panel',
            'verification' => 'Verification Panel',
            default => 'Workspace',
        };

        $status = BillingWorkItem::STATUS_OPTIONS[data_get($activity->meta, 'status')] ?? str((string) data_get($activity->meta, 'status'))->headline()->toString();
        $outcome = BillingWorkItem::OUTCOME_STATUS_OPTIONS[data_get($activity->meta, 'outcome_status')] ?? str((string) data_get($activity->meta, 'outcome_status'))->headline()->toString();
        $priority = BillingWorkItem::PRIORITY_OPTIONS[data_get($activity->meta, 'priority')] ?? str((string) data_get($activity->meta, 'priority'))->headline()->toString();
        $filledProfileFields = (int) data_get($activity->meta, 'filled_profile_fields', 0);
        $answeredQuestions = (int) data_get($activity->meta, 'answered_questions', 0);

        return collect([
            filled(data_get($activity->meta, 'submission_version'))
                ? 'Submission Version: v' . data_get($activity->meta, 'submission_version')
                : null,
            "Source: {$panel}",
            filled(data_get($activity->meta, 'status')) ? "Status: {$status}" : null,
            filled(data_get($activity->meta, 'outcome_status')) ? "Outcome: {$outcome}" : null,
            filled(data_get($activity->meta, 'priority')) ? "Priority: {$priority}" : null,
            "Profile fields captured: {$filledProfileFields}",
            "Question answers stored: {$answeredQuestions}",
        ])->filter()->implode("\n");
    }

    protected function buildAttachmentDetails(BillingWorkItemActivity $activity): ?string
    {
        return collect([
            data_get($activity->meta, 'original_file_name'),
            data_get($activity->meta, 'mime_type'),
            data_get($activity->meta, 'notes'),
        ])->filter()->implode("\n") ?: null;
    }

    protected function buildAttachmentDownloadDetails(BillingWorkItemActivity $activity): ?string
    {
        return collect([
            filled(data_get($activity->meta, 'panel')) ? 'Panel: ' . str((string) data_get($activity->meta, 'panel'))->headline()->toString() : null,
            data_get($activity->meta, 'original_file_name'),
            data_get($activity->meta, 'mime_type'),
        ])->filter()->implode("\n") ?: null;
    }

    protected function buildSnapshotViewDetails(BillingWorkItemActivity $activity): ?string
    {
        return collect([
            filled(data_get($activity->meta, 'panel')) ? 'Panel: ' . str((string) data_get($activity->meta, 'panel'))->headline()->toString() : null,
            filled(data_get($activity->meta, 'submission_version')) ? 'Submission Version: v' . data_get($activity->meta, 'submission_version') : null,
        ])->filter()->implode("\n") ?: null;
    }

    protected function buildAccessDetails(BillingWorkItemActivity $activity): ?string
    {
        return collect([
            filled(data_get($activity->meta, 'panel')) ? 'Panel: ' . str((string) data_get($activity->meta, 'panel'))->headline()->toString() : null,
            filled(data_get($activity->meta, 'status'))
                ? 'Status: ' . (BillingWorkItem::STATUS_OPTIONS[data_get($activity->meta, 'status')] ?? str((string) data_get($activity->meta, 'status'))->headline()->toString())
                : null,
        ])->filter()->implode("\n") ?: null;
    }

    protected function formatSubmissionSnapshot(VerificationFormSubmission $submission): array
    {
        $payload = $submission->payload ?? [];
        $changes = $this->buildSubmissionDiffRows($submission);

        return [
            'headline' => [
                'version' => $submission->version,
                'submitted_at' => optional($submission->created_at)->format('M d, Y h:i A') ?: '-',
                'submitted_by' => $submission->user?->name ?: 'System',
                'panel' => match ($submission->panel) {
                    'clinic' => 'Clinic Panel',
                    'verification' => 'Verification Panel',
                    default => 'Workspace',
                },
                'status' => $this->snapshotOptionLabel(BillingWorkItem::STATUS_OPTIONS, $submission->status),
                'outcome' => $this->snapshotOptionLabel(BillingWorkItem::OUTCOME_STATUS_OPTIONS, $submission->outcome_status),
                'priority' => $this->snapshotOptionLabel(BillingWorkItem::PRIORITY_OPTIONS, $submission->priority),
            ],
            'summary' => $this->formatSnapshotRows(data_get($payload, 'summary', [])),
            'work_item' => $this->formatSnapshotRows(data_get($payload, 'work_item', [])),
            'verification_profile' => $this->formatSnapshotRows(data_get($payload, 'verification_profile', [])),
            'filled_verification_profile' => collect($this->formatSnapshotRows(data_get($payload, 'verification_profile', [])))
                ->reject(fn (array $row): bool => ($row['value'] ?? '-') === '-')
                ->values()
                ->all(),
            'changes' => $changes,
            'form_changes' => collect($changes)
                ->reject(fn (array $change): bool => in_array($change['group'] ?? null, ['Submission Summary', 'Queue State'], true))
                ->reject(fn (array $change): bool => in_array($change['label'] ?? null, ['Public Id', 'Is Pre Registered', 'Quick Reference'], true))
                ->values()
                ->all(),
            'answers' => collect(data_get($payload, 'answers', []))
                ->map(function ($answer): array {
                    $answerValue = $this->normalizeSnapshotValue(data_get($answer, 'answer_value'));
                    $noteValue = $this->normalizeSnapshotValue(data_get($answer, 'note_value'));

                    return [
                        'code' => data_get($answer, 'code') ?: '-',
                        'prompt' => data_get($answer, 'prompt') ?: 'Question',
                        'value' => collect([
                            $answerValue !== '-' ? $answerValue : null,
                            $noteValue !== '-' ? 'Note: ' . $noteValue : null,
                        ])->filter()->implode("\n") ?: '-',
                    ];
                })
                ->values()
                ->all(),
            'coverage_codes' => collect(data_get($payload, 'coverage_codes', []))
                ->map(fn (array $row): array => [
                    'code' => data_get($row, 'code') ?: '-',
                    'description' => data_get($row, 'description') ?: 'Coverage item',
                    'value' => collect([
                        filled(data_get($row, 'coverage_status')) ? 'Status: ' . str((string) data_get($row, 'coverage_status'))->headline() : null,
                        filled(data_get($row, 'coverage_percent')) ? 'Coverage: ' . data_get($row, 'coverage_percent') . '%' : null,
                        filled(data_get($row, 'frequency')) ? 'Frequency: ' . data_get($row, 'frequency') : null,
                    ])->filter()->implode(' | ') ?: '-',
                ])
                ->values()
                ->all(),
            'raw_payload' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}',
        ];
    }

    protected function buildSubmissionDiffRows(VerificationFormSubmission $submission): array
    {
        $currentEntries = $this->buildSubmissionComparableEntries($submission->payload ?? []);
        $previousQuery = $submission->workItem
            ?->formSubmissions()
            ->where('version', '<', $submission->version);

        if ($submission->status === BillingWorkItem::STATUS_DONE) {
            $previousQuery?->where('status', BillingWorkItem::STATUS_DONE);
        }

        $previous = $previousQuery?->latest('version')->first();

        if (! $previous) {
            return collect($currentEntries)
                ->map(function (array $entry): ?array {
                    $after = $this->normalizeSnapshotValue($entry['value'] ?? null);

                    if ($after === '-') {
                        return null;
                    }

                    return [
                        'group' => $entry['group'] ?? 'Verification Audit',
                        'label' => $entry['label'] ?? 'Question',
                        'before' => '-',
                        'after' => $after,
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        $previousEntries = $this->buildSubmissionComparableEntries($previous->payload ?? []);

        return collect(array_unique(array_merge(array_keys($currentEntries), array_keys($previousEntries))))
            ->map(function (string $key) use ($currentEntries, $previousEntries): ?array {
                $current = $currentEntries[$key] ?? null;
                $previous = $previousEntries[$key] ?? null;
                $before = $previous['value'] ?? null;
                $after = $current['value'] ?? null;

                if ($before === $after) {
                    return null;
                }

                $entry = $current ?? $previous;

                return [
                    'group' => $entry['group'] ?? 'Verification Audit',
                    'label' => $entry['label'] ?? str($key)->headline()->toString(),
                    'before' => $this->normalizeSnapshotValue($before),
                    'after' => $this->normalizeSnapshotValue($after),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function buildSubmissionComparableEntries(array $payload): array
    {
        $entries = [];

        foreach (data_get($payload, 'summary', []) as $key => $value) {
            $entryKey = 'summary.' . $key;
            $entries[$entryKey] = $this->describeSubmissionEntry($entryKey, $value);
        }

        foreach (data_get($payload, 'work_item', []) as $key => $value) {
            $entryKey = 'work_item.' . $key;
            $entries[$entryKey] = $this->describeSubmissionEntry($entryKey, $value);
        }

        foreach (data_get($payload, 'verification_profile', []) as $key => $value) {
            $entryKey = 'verification_profile.' . $key;
            $entries[$entryKey] = $this->describeSubmissionEntry($entryKey, $value);
        }

        foreach (data_get($payload, 'answers', []) as $answer) {
            $identifier = data_get($answer, 'code') ?: ('question_' . data_get($answer, 'question_id'));
            $entryKey = 'answers.' . $identifier;
            $answerValue = $this->normalizeSnapshotValue(data_get($answer, 'answer_value'));
            $noteValue = $this->normalizeSnapshotValue(data_get($answer, 'note_value'));
            $entries[$entryKey] = $this->describeSubmissionEntry($entryKey, collect([
                $answerValue !== '-' ? $answerValue : null,
                $noteValue !== '-' ? 'Note: ' . $noteValue : null,
            ])->filter()->implode("\n") ?: null, $answer);
        }

        foreach (data_get($payload, 'coverage_codes', []) as $row) {
            $identifier = data_get($row, 'code') ?: str((string) data_get($row, 'description', 'coverage_code'))->slug('_')->toString();
            $entryKey = 'coverage_codes.' . $identifier;
            $entries[$entryKey] = $this->describeSubmissionEntry($entryKey, collect([
                data_get($row, 'coverage_status'),
                filled(data_get($row, 'coverage_percent')) ? data_get($row, 'coverage_percent') . '%' : null,
                data_get($row, 'frequency'),
                data_get($row, 'service_history'),
                data_get($row, 'notes'),
            ])->filter()->implode(' | '), $row);
        }

        return $entries;
    }

    protected function describeSubmissionEntry(string $key, mixed $value, array $answer = []): array
    {
        return match (true) {
            str_starts_with($key, 'summary.') => [
                'group' => 'Submission Summary',
                'label' => match ($key) {
                    'summary.filled_profile_fields' => 'Profile Fields Captured',
                    'summary.answered_questions' => 'Question Answers Stored',
                    default => str(str_replace('summary.', '', $key))->replace('_', ' ')->headline()->toString(),
                },
                'value' => $value,
            ],
            str_starts_with($key, 'work_item.') => [
                'group' => 'Queue State',
                'label' => match ($key) {
                    'work_item.status' => 'Workflow Status',
                    'work_item.outcome_status' => 'Verification Outcome',
                    'work_item.priority' => 'Priority',
                    'work_item.assigned_to' => 'Assigned To',
                    'work_item.reviewed_by' => 'Reviewed By',
                    'work_item.closed_by' => 'Closed By',
                    'work_item.notes' => 'Queue Notes',
                    'work_item.internal_summary' => 'Internal Summary',
                    default => str(str_replace('work_item.', '', $key))->replace('_', ' ')->headline()->toString(),
                },
                'value' => match ($key) {
                    'work_item.status' => $this->snapshotOptionLabel(BillingWorkItem::STATUS_OPTIONS, is_scalar($value) ? (string) $value : null),
                    'work_item.outcome_status' => $this->snapshotOptionLabel(BillingWorkItem::OUTCOME_STATUS_OPTIONS, is_scalar($value) ? (string) $value : null),
                    'work_item.priority' => $this->snapshotOptionLabel(BillingWorkItem::PRIORITY_OPTIONS, is_scalar($value) ? (string) $value : null),
                    default => $value,
                },
            ],
            str_starts_with($key, 'verification_profile.') => [
                'group' => $this->verificationProfileDiffGroup($key),
                'label' => $this->verificationProfileDiffLabel($key),
                'value' => $value,
            ],
            str_starts_with($key, 'answers.') => [
                'group' => 'Custom Answers',
                'label' => data_get($answer, 'prompt')
                    ? trim((string) data_get($answer, 'prompt')) . (filled(data_get($answer, 'code')) ? ' (' . data_get($answer, 'code') . ')' : '')
                    : str(str_replace('answers.', '', $key))->replace('_', ' ')->headline()->toString(),
                'value' => $value,
            ],
            str_starts_with($key, 'coverage_codes.') => [
                'group' => 'Coverage Codes',
                'label' => trim(collect([
                    data_get($answer, 'code'),
                    data_get($answer, 'description'),
                ])->filter()->implode(' - ')) ?: str(str_replace('coverage_codes.', '', $key))->replace('_', ' ')->headline()->toString(),
                'value' => $value,
            ],
            default => [
                'group' => 'Verification Audit',
                'label' => str($key)->replace('.', ' / ')->headline()->toString(),
                'value' => $value,
            ],
        };
    }

    protected function verificationProfileDiffGroup(string $key): string
    {
        return match ($key) {
            'verification_profile.patient_full_name',
            'verification_profile.patient_dob',
            'verification_profile.patient_identifier',
            'verification_profile.patient_zip',
            'verification_profile.subscriber_name',
            'verification_profile.subscriber_dob',
            'verification_profile.insured_relation' => 'Patient & Subscriber',
            'verification_profile.insurance_provider_name',
            'verification_profile.payer_id',
            'verification_profile.group_name',
            'verification_profile.group_number',
            'verification_profile.network_status',
            'verification_profile.effective_date',
            'verification_profile.plan_type',
            'verification_profile.fee_schedule' => 'Insurance Details',
            'verification_profile.annual_maximum',
            'verification_profile.annual_maximum_remaining',
            'verification_profile.individual_deductible',
            'verification_profile.individual_deductible_remaining',
            'verification_profile.family_deductible',
            'verification_profile.family_deductible_remaining' => 'Benefits & Deductibles',
            'verification_profile.appointment_date',
            'verification_profile.appointment_time',
            'verification_profile.provider_name',
            'verification_profile.location_name',
            'verification_profile.requested_by_name',
            'verification_profile.requested_from_panel' => 'Request Context',
            default => 'Verification Profile',
        };
    }

    protected function verificationProfileDiffLabel(string $key): string
    {
        return match ($key) {
            'verification_profile.patient_full_name' => 'Patient Name',
            'verification_profile.patient_dob' => 'Patient Date of Birth',
            'verification_profile.patient_identifier' => 'Member ID',
            'verification_profile.patient_zip' => 'Patient ZIP',
            'verification_profile.subscriber_name' => 'Subscriber Name',
            'verification_profile.subscriber_dob' => 'Subscriber Date of Birth',
            'verification_profile.insured_relation' => 'Relationship to Patient',
            'verification_profile.insurance_provider_name' => 'Insurance Provider',
            'verification_profile.payer_id' => 'Payer ID',
            'verification_profile.group_name' => 'Group Name',
            'verification_profile.group_number' => 'Group Number',
            'verification_profile.network_status' => 'Network Status',
            'verification_profile.effective_date' => 'Effective Date',
            'verification_profile.plan_type' => 'Plan Type',
            'verification_profile.fee_schedule' => 'Fee Schedule',
            'verification_profile.annual_maximum' => 'Annual Maximum',
            'verification_profile.annual_maximum_remaining' => 'Annual Maximum Remaining',
            'verification_profile.individual_deductible' => 'Individual Deductible',
            'verification_profile.individual_deductible_remaining' => 'Individual Deductible Remaining',
            'verification_profile.family_deductible' => 'Family Deductible',
            'verification_profile.family_deductible_remaining' => 'Family Deductible Remaining',
            'verification_profile.appointment_date' => 'Appointment Date',
            'verification_profile.appointment_time' => 'Appointment Time',
            'verification_profile.provider_name' => 'Provider Name',
            'verification_profile.location_name' => 'Location Name',
            'verification_profile.requested_by_name' => 'Requested By',
            'verification_profile.requested_from_panel' => 'Requested From Panel',
            default => str(str_replace('verification_profile.', '', $key))->replace('_', ' ')->headline()->toString(),
        };
    }

    protected function injectStaleTimelineEvents(Collection $activities, BillingWorkItem $record): Collection
    {
        $lastActivity = $record->activities()->latest('created_at')->first();
        $lastTouchedAt = $lastActivity?->created_at ?? $record->updated_at ?? $record->created_at;

        if (! $lastTouchedAt) {
            return $activities;
        }

        $hoursSinceUpdate = (int) $lastTouchedAt->diffInHours(now());
        $staleEvent = null;

        if ($record->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE && $hoursSinceUpdate >= 24) {
            $staleEvent = [
                'type' => 'Waiting On Clinic Aging',
                'description' => 'This request has been waiting on clinic response longer than the normal follow-up window.',
                'details' => collect([
                    'Current status: Awaiting Clinic Response',
                    'Last workflow update: ' . $lastTouchedAt->format('M d, Y h:i A'),
                    'Time waiting: ' . $this->humanizeHours($hoursSinceUpdate),
                    $record->due_at ? 'Original due target: ' . $record->due_at->format('M d, Y h:i A') : null,
                ])->filter()->implode("\n"),
                'author' => 'System Monitor',
                'created_at' => now()->format('M d, Y h:i A'),
                'created_at_raw' => now(),
                'tone' => 'amber',
                'submission_id' => null,
            ];
        } elseif (in_array($record->normalized_status, [
            BillingWorkItem::STATUS_IN_PROGRESS,
            BillingWorkItem::STATUS_REVIEW,
            BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
        ], true) && $hoursSinceUpdate >= 24) {
            $staleEvent = [
                'type' => 'No Recent Update',
                'description' => 'This verification has been sitting without a new submission or workflow action for an extended period.',
                'details' => collect([
                    'Current status: ' . (BillingWorkItem::STATUS_OPTIONS[$record->normalized_status] ?? str($record->normalized_status)->headline()->toString()),
                    'Last workflow update: ' . $lastTouchedAt->format('M d, Y h:i A'),
                    'Time idle: ' . $this->humanizeHours($hoursSinceUpdate),
                    $record->due_at ? 'Due at: ' . $record->due_at->format('M d, Y h:i A') : null,
                ])->filter()->implode("\n"),
                'author' => 'System Monitor',
                'created_at' => now()->format('M d, Y h:i A'),
                'created_at_raw' => now(),
                'tone' => $record->normalized_status === BillingWorkItem::STATUS_RETURNED_FOR_REWORK ? 'rose' : 'amber',
                'submission_id' => null,
            ];
        }

        if (! $staleEvent) {
            return $activities;
        }

        return collect([$staleEvent])->merge($activities)->values();
    }

    protected function humanizeHours(int $hours): string
    {
        if ($hours < 24) {
            return $hours . ' hour' . ($hours === 1 ? '' : 's');
        }

        $days = (int) floor($hours / 24);
        $remainingHours = $hours % 24;

        if ($remainingHours === 0) {
            return $days . ' day' . ($days === 1 ? '' : 's');
        }

        return $days . ' day' . ($days === 1 ? '' : 's') . ' ' . $remainingHours . ' hour' . ($remainingHours === 1 ? '' : 's');
    }

    protected function formatSnapshotRows(array $rows): array
    {
        return collect($rows)
            ->map(function ($value, $key): array {
                return [
                    'label' => str((string) $key)->replace('_', ' ')->headline()->toString(),
                    'value' => $this->normalizeSnapshotValue($value),
                ];
            })
            ->values()
            ->all();
    }

    protected function normalizeSnapshotValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item) => $this->normalizeSnapshotValue($item))
                ->filter(fn ($item) => filled($item) && $item !== '-')
                ->implode(', ') ?: '-';
        }

        if (blank($value) && $value !== 0 && $value !== 0.0 && $value !== '0') {
            return '-';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('M d, Y h:i A');
        }

        return (string) $value;
    }

    protected function snapshotOptionLabel(array $options, ?string $value): string
    {
        if (blank($value)) {
            return '-';
        }

        return $options[$value] ?? str($value)->headline()->toString();
    }

    protected function resolveTone(?string $value, array $tones): string
    {
        return $tones[$value] ?? 'slate';
    }

    protected function money($value): string
    {
        if (blank($value) && $value !== 0 && $value !== 0.0) {
            return '-';
        }

        return '$' . number_format((float) $value, 2);
    }

    protected function percent($value): string
    {
        if (blank($value) && $value !== 0 && $value !== 0.0) {
            return '-';
        }

        return number_format((float) $value, 0) . '%';
    }

    protected function countCompleted(array $values): int
    {
        return collect($values)->filter(function ($value): bool {
            if ($value === 0 || $value === 0.0 || $value === '0') {
                return true;
            }

            return filled($value);
        })->count();
    }

    protected function resolveCoverageRole(?string $patientName, ?string $subscriberName, ?string $insuredRelation): string
    {
        $relation = filled($insuredRelation) ? str($insuredRelation)->lower()->trim()->toString() : null;

        if (filled($relation) && in_array($relation, ['self', 'subscriber', 'primary', 'policy holder', 'policyholder'], true)) {
            return 'Primary holder';
        }

        if (filled($relation)) {
            return str($insuredRelation)->headline()->toString();
        }

        if (filled($patientName) && filled($subscriberName) && strcasecmp(trim($patientName), trim($subscriberName)) === 0) {
            return 'Primary holder';
        }

        if (filled($subscriberName)) {
            return 'Family beneficiary';
        }

        return '-';
    }
}
