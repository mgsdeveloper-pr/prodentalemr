<?php

namespace App\Services\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Models\VerificationFormSubmission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QualityService
{
    public function __construct(
        protected VerificationAuditService $audit,
    ) {}

    public function returnForCorrection(BillingWorkItem $request, string $reason, ?User $actor = null): BillingWorkItem
    {
        $actor ??= auth()->user();
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'return_reason' => 'A correction reason is required.',
            ]);
        }

        $this->authorizeTransition($request, $actor, BillingWorkItem::STATUS_RETURNED_FOR_REWORK);

        $request->return_reason = $reason;
        $request->reviewed_by = $actor?->getAuthIdentifier() ?? $request->reviewed_by;

        $request = app(StatusService::class)->transition($request, BillingWorkItem::STATUS_RETURNED_FOR_REWORK);

        app(TimelineService::class)->record($request, 'verification_qa_rejected', 'Audit returned the verification for correction.', [
            'reason' => $reason,
            'reviewed_by' => $actor?->name,
        ], $actor);

        return $request;
    }

    public function requestCorrection(BillingWorkItem $request, string $reason, ?User $actor = null, array $requestedFields = []): BillingWorkItem
    {
        $actor ??= auth()->user();
        $reason = trim($reason);
        $requestedFields = collect($requestedFields)
            ->mapWithKeys(fn ($label, $key): array => [trim((string) $key) => trim((string) $label)])
            ->filter(fn (string $label, string $key): bool => $key !== '' && $label !== '')
            ->all();

        if ($reason === '') {
            throw ValidationException::withMessages([
                'correction_reason' => 'Please explain what needs to be corrected.',
            ]);
        }

        $requestedFields = $requestedFields ?: ['general' => 'General verification result'];

        $this->authorizeTransition($request, $actor, BillingWorkItem::STATUS_RETURNED_FOR_REWORK);

        $baseline = $request->formSubmissions()
            ->where('status', BillingWorkItem::STATUS_DONE)
            ->latest('version')
            ->first();

        $request->return_reason = $reason;
        $request = app(StatusService::class)->transition($request, BillingWorkItem::STATUS_RETURNED_FOR_REWORK);

        app(TimelineService::class)->record($request, 'clinic_correction_requested', 'Clinic requested a correction to the verification result.', [
            'reason' => $reason,
            'requested_by' => $actor?->name,
            'requested_fields' => $requestedFields,
            'baseline_submission_id' => $baseline?->getKey(),
            'baseline_submission_version' => $baseline?->version,
        ], $actor);

        return $request;
    }

    public function submitForReview(BillingWorkItem $request, ?User $actor = null): BillingWorkItem
    {
        $actor ??= auth()->user();
        $this->authorizeTransition($request, $actor, BillingWorkItem::STATUS_REVIEW);

        $missing = $this->audit->missingRequiredAnswers($request);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'audit' => 'Complete all required verification answers before sending this request to Audit.',
            ]);
        }

        $request = app(StatusService::class)->transition($request, BillingWorkItem::STATUS_REVIEW);

        app(TimelineService::class)->record($request, 'verification_submitted_for_qa', 'Verification submitted for audit review.', [
            'submitted_by' => $actor?->name,
        ], $actor);

        return $request;
    }

    public function approve(BillingWorkItem $request, ?User $actor = null): BillingWorkItem
    {
        return DB::transaction(function () use ($request, $actor): BillingWorkItem {
            $request = BillingWorkItem::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
            $actor ??= auth()->user();
            $this->authorizeTransition($request, $actor, BillingWorkItem::STATUS_DONE);

            $missing = $this->audit->missingRequiredAnswers($request);

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'audit' => 'Complete all required verification answers before approving this request.',
                ]);
            }

            if (app(CoverageAssessmentService::class)->isInactive($request)) {
                $request->outcome_status = 'inactive_coverage';
            } elseif (data_get($request->coverage_assessment, 'outcome') === 'active') {
                $request->outcome_status = 'verified';
            } elseif (in_array($request->outcome_status, ['pending', 'audit_required'], true)) {
                $request->outcome_status = 'verified';
            }

            if (! $request->hasFinalOutcome()) {
                throw ValidationException::withMessages([
                    'outcome_status' => 'Record a final verification result before approving this request.',
                ]);
            }

            $request->reviewed_by = $actor?->getAuthIdentifier() ?? $request->reviewed_by;

            $request = app(StatusService::class)->transition($request, BillingWorkItem::STATUS_DONE);

            $completion = $this->captureCompletionSnapshot($request, $actor);

            app(TimelineService::class)->record($request, 'verification_qa_approved', 'Audit approved the verification.', [
                'reviewed_by' => $actor?->name,
                'submission_id' => $completion?->getKey(),
                'submission_version' => $completion?->version,
            ], $actor);

            return $request;
        });
    }

    protected function captureCompletionSnapshot(BillingWorkItem $request, ?User $actor): ?VerificationFormSubmission
    {
        $latest = $request->formSubmissions()->latest('version')->first();

        if ($request->coverage_assessment === null && (! $latest || $latest->status === BillingWorkItem::STATUS_DONE)) {
            return $latest;
        }

        $payload = $latest->payload ?? [];
        if ($request->coverage_assessment !== null) {
            $request->load(['verificationProfile', 'verificationFormAnswers.question', 'verificationCoverageCodes']);
            $payload['template_snapshot'] = $request->verification_template_snapshot;
            $payload['verification_profile'] = $request->verificationProfile?->attributesToArray() ?? [];
            $payload['coverage_codes'] = $request->verificationCoverageCodes->toArray();
            $payload['answers'] = $request->verificationFormAnswers->map(fn ($answer) => [
                'question_id' => $answer->verification_form_question_id, 'prompt' => $answer->question?->prompt,
                'answer_value' => $answer->answer_value, 'note_value' => $answer->note_value,
            ])->all();
        }
        data_set($payload, 'work_item.coverage_assessment', $request->coverage_assessment);
        data_set($payload, 'work_item.status', BillingWorkItem::STATUS_DONE);
        data_set($payload, 'work_item.outcome_status', $request->outcome_status);
        data_set($payload, 'work_item.priority', $request->priority);
        data_set($payload, 'work_item.reviewed_by', $actor?->name ?: $request->reviewedBy?->name);
        data_set($payload, 'work_item.closed_by', $request->closedBy?->name);

        return $request->formSubmissions()->create([
            'user_id' => $actor?->getAuthIdentifier(),
            'panel' => 'verification',
            'status' => BillingWorkItem::STATUS_DONE,
            'outcome_status' => $request->outcome_status,
            'priority' => $request->priority,
            'version' => ((int) $request->formSubmissions()->max('version')) + 1,
            'payload' => $payload,
        ]);
    }

    protected function authorizeTransition(BillingWorkItem $request, ?User $actor, string $targetStatus): void
    {
        if (! $actor || ! $request->canUserTransitionTo($actor, $targetStatus)) {
            throw new AuthorizationException('You are not authorized to perform this verification workflow action.');
        }
    }
}
