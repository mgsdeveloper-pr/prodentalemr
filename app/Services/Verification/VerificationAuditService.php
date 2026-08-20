<?php

namespace App\Services\Verification;

use App\Models\BillingWorkItem;
use App\Models\VerificationFormQuestion;
use Illuminate\Support\Collection;

class VerificationAuditService
{
    public function missingRequiredAnswers(BillingWorkItem $request): array
    {
        $request->load([
            'clinic',
            'verificationProfile',
            'verificationFormAnswers',
            'verificationCoverageCodes',
        ]);

        $formType = $request->verificationProfile?->form_type ?: 'full_form';
        $questions = $this->requiredApplicableQuestions(
            $request,
            VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
            $formType,
        );
        $answers = $request->verificationFormAnswers->keyBy('verification_form_question_id');
        $missing = [];

        foreach ($questions->where('input_type', '!=', 'frequency_row') as $question) {
            if ($question->isConditionalQuestion()) {
                $parent = $questions->firstWhere('id', $question->parent_question_id)
                    ?? VerificationFormQuestion::query()->find($question->parent_question_id);

                if (! $parent || ! $question->matchesTrigger($this->questionValue($request, $parent, $answers))) {
                    continue;
                }
            }

            foreach ($this->questionFieldValues($request, $question, $answers) as $field => $value) {
                if ($this->isBlank($value)) {
                    $missing['question:' . $question->getKey() . ':' . $field] = $question->prompt;
                }
            }
        }

        foreach ($questions->where('input_type', 'frequency_row') as $question) {
            $coverage = $request->verificationCoverageCodes->first(function ($row) use ($question): bool {
                if (filled($question->code) && filled($row->code)) {
                    return strcasecmp(trim((string) $row->code), trim((string) $question->code)) === 0;
                }

                return strcasecmp(trim((string) $row->description), trim((string) $question->prompt)) === 0;
            });

            if (! $coverage || ($this->isBlank($coverage->coverage_status) && $this->isBlank($coverage->coverage_percent))) {
                $missing['frequency:' . $question->getKey()] = $question->prompt;
            }
        }

        return $missing;
    }

    public function isComplete(BillingWorkItem $request): bool
    {
        return $this->missingRequiredAnswers($request) === [];
    }

    public function requiredApplicableQuestions(
        BillingWorkItem $request,
        string $templateKey,
        string $formType,
        ?bool $frequencyRows = null,
    ): Collection {
        return $this->applicableQuestions($request, $templateKey, $formType, $frequencyRows)
            ->where('is_required_for_audit', true)
            ->values();
    }

    /**
     * Return the active questions that belong to the exact form shown for a request.
     */
    public function applicableQuestions(
        BillingWorkItem $request,
        string $templateKey,
        string $formType,
        ?bool $frequencyRows = null,
    ): Collection {
        $query = VerificationFormQuestion::query()
            ->where('template_key', VerificationFormQuestion::normalizeTemplateKey($templateKey))
            ->where('is_active', true)
            ->whereIn('form_type', ['both', $formType]);

        if ($frequencyRows === true) {
            $query->where('input_type', 'frequency_row');
        } elseif ($frequencyRows === false) {
            $query->where('input_type', '!=', 'frequency_row');
        }

        if (filled($request->verification_template_version_id)) {
            $query->where('template_version_id', $request->verification_template_version_id);
        } else {
            $query->visibleForClinic(
                filled($request->clinic_id) ? (int) $request->clinic_id : null,
                filled($request->organization_id) ? (int) $request->organization_id : null,
            );
        }

        return $query
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    protected function questionValue(BillingWorkItem $request, VerificationFormQuestion $question, Collection $answers): mixed
    {
        return collect($this->questionFieldValues($request, $question, $answers))
            ->first(fn ($value): bool => ! $this->isBlank($value));
    }

    protected function questionFieldValues(BillingWorkItem $request, VerificationFormQuestion $question, Collection $answers): array
    {
        if (! $question->is_builtin) {
            return [
                'custom_question_' . $question->getKey() => $answers->get($question->getKey())?->answer_value,
            ];
        }

        $fields = array_values(array_filter([$question->field_key, $question->secondary_field_key]));

        return collect($fields)->mapWithKeys(fn (string $field): array => [
            $field => $this->builtInValue($request, $field),
        ])->all();
    }

    protected function builtInValue(BillingWorkItem $request, string $field): mixed
    {
        if ($field === 'context_clinic_name') {
            return $request->clinic?->clinic_name;
        }

        $profileField = str_starts_with($field, 'vf_') ? substr($field, 3) : $field;

        if ($request->verificationProfile && array_key_exists($profileField, $request->verificationProfile->getAttributes())) {
            return $request->verificationProfile->getAttribute($profileField);
        }

        return $request->getAttribute($field);
    }

    protected function isBlank(mixed $value): bool
    {
        return blank($value) && $value !== 0 && $value !== '0';
    }
}
