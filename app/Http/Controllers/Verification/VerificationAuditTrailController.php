<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemActivity;
use App\Support\AdminClinicScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class VerificationAuditTrailController extends Controller
{
    public function downloadForAdmin(BillingWorkItem $billingWorkItem): Response
    {
        $this->ensureAdminCanAccess($billingWorkItem);

        $billingWorkItem->load([
            'organization',
            'clinic',
            'location',
            'patient',
            'provider',
            'assignedTo',
            'reviewedBy',
            'closedBy',
            'verificationProfile',
            'formSubmissions.user',
            'activities.user',
        ]);

        $billingWorkItem->recordActivity('verification_audit_exported', 'Verification audit trail exported.', [
            'panel' => 'verification',
            'user_name' => auth()->user()?->name,
        ]);

        $pdf = Pdf::loadView('pdf.verifications.audit-timeline', [
            'workItem' => $billingWorkItem,
            'summary' => $this->buildSummary($billingWorkItem),
            'timeline' => $this->buildTimeline($billingWorkItem),
            'submissions' => $this->buildSubmissions($billingWorkItem),
        ]);

        $fileName = 'verification-audit-' . ($billingWorkItem->reference_number ?: $billingWorkItem->id) . '.pdf';

        return response(
            $pdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    protected function ensureAdminCanAccess(BillingWorkItem $billingWorkItem): void
    {
        abort_unless(auth()->user()?->canAccessSaasRevenueOperations(), 403);

        $selectedClinicId = AdminClinicScope::selectedClinicId();

        if ($selectedClinicId) {
            abort_unless((int) $billingWorkItem->clinic_id === (int) $selectedClinicId, 403);
        }
    }

    protected function buildSummary(BillingWorkItem $workItem): array
    {
        return [
            'reference' => $workItem->reference_number ?: '-',
            'status' => BillingWorkItem::STATUS_OPTIONS[$workItem->normalized_status] ?? str($workItem->normalized_status)->headline()->toString(),
            'outcome' => BillingWorkItem::OUTCOME_STATUS_OPTIONS[$workItem->outcome_status] ?? str((string) $workItem->outcome_status)->headline()->toString(),
            'priority' => BillingWorkItem::PRIORITY_OPTIONS[$workItem->priority] ?? str((string) $workItem->priority)->headline()->toString(),
            'organization' => $workItem->organization?->name ?: '-',
            'clinic' => $workItem->clinic?->clinic_name ?: '-',
            'patient' => $workItem->verificationProfile?->patient_full_name ?: ($workItem->patient?->full_name ?: '-'),
            'appointment_date' => optional($workItem->verificationProfile?->appointment_date)->format('M d, Y') ?: '-',
            'assigned_to' => $workItem->assignedTo?->name ?: 'Unassigned',
            'reviewed_by' => $workItem->reviewedBy?->name ?: '-',
            'closed_by' => $workItem->closedBy?->name ?: '-',
            'due_at' => optional($workItem->due_at)->format('M d, Y h:i A') ?: '-',
        ];
    }

    protected function buildTimeline(BillingWorkItem $workItem): array
    {
        $timeline = $workItem->activities
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (BillingWorkItemActivity $activity): array => [
                'type' => match ($activity->activity_type) {
                    'info_requested_from_clinic' => 'Information Requested',
                    'clinic_response_received' => 'Clinic Responded',
                    'returned_for_rework' => 'Returned for Rework',
                    'rework_resumed' => 'Rework Started',
                    'rework_completed' => 'Rework Completed',
                    'attachment_added' => 'Attachment Uploaded',
                    'attachment_downloaded' => 'Attachment Downloaded',
                    'submission_snapshot_viewed' => 'Snapshot Viewed',
                    'verification_detail_viewed' => 'Detail View Opened',
                    'verification_console_opened' => 'Verification Console Opened',
                    'form_submitted' => 'Form Submitted',
                    default => str($activity->activity_type)->replace('_', ' ')->title()->toString(),
                },
                'description' => $activity->description,
                'details' => $this->buildActivityDetails($activity),
                'author' => $activity->user?->name ?: 'System',
                'created_at' => optional($activity->created_at)->format('M d, Y h:i A') ?: '-',
            ])
            ->all();

        $lastActivity = $workItem->activities->sortByDesc('created_at')->first();
        $lastTouchedAt = $lastActivity?->created_at ?? $workItem->updated_at ?? $workItem->created_at;

        if ($lastTouchedAt) {
            $hoursSinceUpdate = (int) $lastTouchedAt->diffInHours(now());

            if ($workItem->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE && $hoursSinceUpdate >= 24) {
                array_unshift($timeline, [
                    'type' => 'Waiting On Clinic Aging',
                    'description' => 'This request has been waiting on clinic response longer than the normal follow-up window.',
                    'details' => 'Last workflow update: ' . $lastTouchedAt->format('M d, Y h:i A') . "\nTime waiting: " . $this->humanizeHours($hoursSinceUpdate),
                    'author' => 'System Monitor',
                    'created_at' => now()->format('M d, Y h:i A'),
                ]);
            } elseif (in_array($workItem->normalized_status, [
                BillingWorkItem::STATUS_IN_PROGRESS,
                BillingWorkItem::STATUS_REVIEW,
                BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
            ], true) && $hoursSinceUpdate >= 24) {
                array_unshift($timeline, [
                    'type' => 'No Recent Update',
                    'description' => 'This verification has been sitting without a new submission or workflow action for an extended period.',
                    'details' => 'Last workflow update: ' . $lastTouchedAt->format('M d, Y h:i A') . "\nTime idle: " . $this->humanizeHours($hoursSinceUpdate),
                    'author' => 'System Monitor',
                    'created_at' => now()->format('M d, Y h:i A'),
                ]);
            }
        }

        return $timeline;
    }

    protected function buildSubmissions(BillingWorkItem $workItem): array
    {
        return $workItem->formSubmissions
            ->sortByDesc('version')
            ->values()
            ->map(function ($submission): array {
                $payload = $submission->payload ?? [];

                return [
                    'version' => $submission->version,
                    'submitted_at' => optional($submission->created_at)->format('M d, Y h:i A') ?: '-',
                    'submitted_by' => $submission->user?->name ?: 'System',
                    'panel' => match ($submission->panel) {
                        'clinic' => 'Clinic Panel',
                        'verification' => 'Verification Panel',
                        default => 'Workspace',
                    },
                    'status' => BillingWorkItem::STATUS_OPTIONS[$submission->status] ?? str((string) $submission->status)->headline()->toString(),
                    'outcome' => BillingWorkItem::OUTCOME_STATUS_OPTIONS[$submission->outcome_status] ?? str((string) $submission->outcome_status)->headline()->toString(),
                    'priority' => BillingWorkItem::PRIORITY_OPTIONS[$submission->priority] ?? str((string) $submission->priority)->headline()->toString(),
                    'profile_fields' => data_get($payload, 'summary.filled_profile_fields', 0),
                    'answered_questions' => data_get($payload, 'summary.answered_questions', 0),
                    'changes' => $this->buildSubmissionDiffRows($submission),
                ];
            })
            ->all();
    }

    protected function buildSubmissionDiffRows($submission): array
    {
        $previous = $submission->workItem
            ?->formSubmissions()
            ->where('version', '<', $submission->version)
            ->latest('version')
            ->first();

        if (! $previous) {
            return [];
        }

        $currentEntries = $this->buildSubmissionComparableEntries($submission->payload ?? []);
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
            $entries[$entryKey] = $this->describeSubmissionEntry($entryKey, data_get($answer, 'answer_value'), $answer);
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

    protected function buildActivityDetails(BillingWorkItemActivity $activity): ?string
    {
        return match ($activity->activity_type) {
            'info_requested_from_clinic' => data_get($activity->meta, 'info_request_reason'),
            'clinic_response_received' => data_get($activity->meta, 'clinic_response_note'),
            'returned_for_rework' => data_get($activity->meta, 'return_reason'),
            'attachment_added' => collect([
                data_get($activity->meta, 'original_file_name'),
                data_get($activity->meta, 'mime_type'),
                data_get($activity->meta, 'notes'),
            ])->filter()->implode("\n"),
            'attachment_downloaded' => collect([
                filled(data_get($activity->meta, 'panel')) ? 'Panel: ' . str((string) data_get($activity->meta, 'panel'))->headline()->toString() : null,
                data_get($activity->meta, 'original_file_name'),
                data_get($activity->meta, 'mime_type'),
            ])->filter()->implode("\n"),
            'submission_snapshot_viewed' => collect([
                filled(data_get($activity->meta, 'panel')) ? 'Panel: ' . str((string) data_get($activity->meta, 'panel'))->headline()->toString() : null,
                filled(data_get($activity->meta, 'submission_version')) ? 'Submission Version: v' . data_get($activity->meta, 'submission_version') : null,
            ])->filter()->implode("\n"),
            'verification_detail_viewed',
            'verification_console_opened' => collect([
                filled(data_get($activity->meta, 'panel')) ? 'Panel: ' . str((string) data_get($activity->meta, 'panel'))->headline()->toString() : null,
                filled(data_get($activity->meta, 'status')) ? 'Status: ' . (BillingWorkItem::STATUS_OPTIONS[data_get($activity->meta, 'status')] ?? str((string) data_get($activity->meta, 'status'))->headline()->toString()) : null,
            ])->filter()->implode("\n"),
            'form_submitted' => collect([
                filled(data_get($activity->meta, 'submission_version')) ? 'Submission Version: v' . data_get($activity->meta, 'submission_version') : null,
                filled(data_get($activity->meta, 'panel')) ? 'Source: ' . str((string) data_get($activity->meta, 'panel'))->headline()->toString() : null,
                filled(data_get($activity->meta, 'status')) ? 'Status: ' . (BillingWorkItem::STATUS_OPTIONS[data_get($activity->meta, 'status')] ?? str((string) data_get($activity->meta, 'status'))->headline()->toString()) : null,
                filled(data_get($activity->meta, 'outcome_status')) ? 'Outcome: ' . (BillingWorkItem::OUTCOME_STATUS_OPTIONS[data_get($activity->meta, 'outcome_status')] ?? str((string) data_get($activity->meta, 'outcome_status'))->headline()->toString()) : null,
                filled(data_get($activity->meta, 'priority')) ? 'Priority: ' . (BillingWorkItem::PRIORITY_OPTIONS[data_get($activity->meta, 'priority')] ?? str((string) data_get($activity->meta, 'priority'))->headline()->toString()) : null,
                'Profile fields captured: ' . (int) data_get($activity->meta, 'filled_profile_fields', 0),
                'Question answers stored: ' . (int) data_get($activity->meta, 'answered_questions', 0),
            ])->filter()->implode("\n"),
            default => null,
        };
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
}
