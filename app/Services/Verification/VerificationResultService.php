<?php

namespace App\Services\Verification;

use App\Models\BillingWorkItem;
use App\Models\VerificationFormSubmission;
use Carbon\Carbon;

class VerificationResultService
{
    public function completionSubmission(BillingWorkItem $request): ?VerificationFormSubmission
    {
        if ($request->normalized_status !== BillingWorkItem::STATUS_DONE) {
            return null;
        }

        return $request->formSubmissions()
            ->where('status', BillingWorkItem::STATUS_DONE)
            ->latest('version')
            ->first();
    }

    public function recordedData(BillingWorkItem $request, ?VerificationFormSubmission $submission = null): array
    {
        $submission ??= $this->completionSubmission($request);
        $payload = $submission?->payload ?? [];
        $profile = data_get($payload, 'verification_profile');
        $workItem = data_get($payload, 'work_item');

        return [
            'submission' => $submission,
            'profile' => is_array($profile) ? $profile : ($request->verificationProfile?->getAttributes() ?? []),
            'work_item' => is_array($workItem) ? $workItem : [
                'status' => $request->normalized_status,
                'outcome_status' => $request->outcome_status,
                'notes' => $request->notes,
                'internal_summary' => $request->internal_summary,
            ],
            'answers' => is_array(data_get($payload, 'answers')) ? data_get($payload, 'answers') : [],
        ];
    }

    public function summary(BillingWorkItem $request, ?VerificationFormSubmission $submission = null): array
    {
        $data = $this->recordedData($request, $submission);
        $profile = $data['profile'];
        $outcome = (string) data_get($data, 'work_item.outcome_status', $request->outcome_status);

        if (($submission?->status === BillingWorkItem::STATUS_DONE || $request->normalized_status === BillingWorkItem::STATUS_DONE)
            && ! in_array($outcome, BillingWorkItem::FINAL_OUTCOME_STATUSES, true)) {
            $outcome = '';
        }

        $preventive = $profile['coverage_preventive'] ?? $profile['coverage_diagnostic'] ?? null;

        return [
            'outcome_status' => $outcome ?: null,
            'eligibility_status' => $outcome !== ''
                ? (BillingWorkItem::OUTCOME_STATUS_OPTIONS[$outcome] ?? str($outcome)->headline()->toString())
                : 'Not recorded',
            'effective_date' => $this->date($profile['effective_date'] ?? null),
            'network_status' => $this->networkStatus($profile),
            'annual_maximum' => $this->money($profile['annual_maximum'] ?? null),
            'annual_maximum_remaining' => $this->money($profile['annual_maximum_remaining'] ?? null),
            'individual_deductible' => $this->money($profile['individual_deductible'] ?? null),
            'individual_deductible_remaining' => $this->money($profile['individual_deductible_remaining'] ?? null),
            'coverage_preventive' => $this->percentage($preventive),
            'coverage_basic' => $this->percentage($profile['coverage_basic_restorative'] ?? null),
            'coverage_major' => $this->percentage($profile['coverage_major_restorative'] ?? null),
            'coverage_orthodontic' => $this->percentage($profile['ortho_benefit'] ?? null),
        ];
    }

    public function applyRecordedDataToPdfState(BillingWorkItem $request, array $state, ?VerificationFormSubmission $submission = null): array
    {
        if (! $submission && $request->normalized_status !== BillingWorkItem::STATUS_DONE) {
            return $state;
        }

        $data = $this->recordedData($request, $submission);

        foreach ($data['profile'] as $key => $value) {
            $state['vf_' . $key] = $value;
        }

        foreach ($data['answers'] as $answer) {
            $questionId = (int) ($answer['question_id'] ?? 0);

            if ($questionId > 0) {
                $state['custom_question_' . $questionId] = $answer['answer_value'] ?? null;
            }
        }

        $state['notes'] = data_get($data, 'work_item.notes', $state['notes'] ?? null);
        $state['internal_summary'] = data_get($data, 'work_item.internal_summary', $state['internal_summary'] ?? null);

        return $state;
    }

    public function outcomeLabel(BillingWorkItem $request, ?VerificationFormSubmission $submission = null): string
    {
        return $this->summary($request, $submission)['eligibility_status'];
    }

    protected function date(mixed $value): string
    {
        if (! filled($value)) {
            return 'Not recorded';
        }

        try {
            return Carbon::parse($value)->format('M d, Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    protected function money(mixed $value): string
    {
        return filled($value) ? '$' . number_format((float) $value, 2) : 'Not recorded';
    }

    protected function percentage(mixed $value): string
    {
        if (! filled($value)) {
            return 'Not recorded';
        }

        $display = trim((string) $value);

        if (str_contains($display, '%') || ! is_numeric($display)) {
            return $display;
        }

        return rtrim(rtrim(number_format((float) $display, 2), '0'), '.') . '%';
    }

    protected function networkStatus(array $profile): string
    {
        if (filled($profile['network_status'] ?? null)) {
            return str((string) $profile['network_status'])->headline()->toString();
        }

        if (! array_key_exists('is_provider_in_network', $profile) || is_null($profile['is_provider_in_network'])) {
            return 'Not recorded';
        }

        return filter_var($profile['is_provider_in_network'], FILTER_VALIDATE_BOOL) ? 'In Network' : 'Out of Network';
    }
}
