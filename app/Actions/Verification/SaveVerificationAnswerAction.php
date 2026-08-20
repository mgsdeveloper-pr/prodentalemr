<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\VerificationFormAnswer;
use App\Models\VerificationFormQuestion;
use App\Models\User;
use App\Support\VerificationTemplateVersionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class SaveVerificationAnswerAction
{
    public function __construct(
        protected VerificationTemplateVersionService $templates,
    ) {
    }

    public function execute(BillingWorkItem $workItem, int $questionId, mixed $answerValue = null, mixed $noteValue = null, ?User $actor = null): ?VerificationFormAnswer
    {
        $actor ??= auth()->user();

        if ($workItem->normalized_status === BillingWorkItem::STATUS_DONE) {
            throw new AuthorizationException('Completed verification answers are locked for audit history.');
        }

        if ($actor && ! $workItem->verificationUserCanEditVerification($actor) && ! $workItem->clinicUserCanEditVerification($actor)) {
            throw new AuthorizationException('You are not authorized to edit this verification request.');
        }

        $workItem = $this->templates->attachSnapshotToWorkItem($workItem);
        $question = $this->resolveQuestionForRequest($workItem, $questionId);
        $answerValue = $this->normalizeAnswerValue($question, $answerValue);
        $noteValue = is_scalar($noteValue) ? trim((string) $noteValue) : null;

        if ($this->isBlank($answerValue) && $this->isBlank($noteValue)) {
            $workItem->verificationFormAnswers()
                ->where('verification_form_question_id', $question->getKey())
                ->delete();

            return null;
        }

        return $workItem->verificationFormAnswers()->updateOrCreate(
            ['verification_form_question_id' => $question->getKey()],
            [
                'answer_value' => $answerValue,
                'note_value' => $noteValue,
            ],
        );
    }

    protected function resolveQuestionForRequest(BillingWorkItem $workItem, int $questionId): VerificationFormQuestion
    {
        $question = VerificationFormQuestion::query()
            ->whereKey($questionId)
            ->where('template_version_id', $workItem->verification_template_version_id)
            ->where('is_active', true)
            ->first();

        if (! $question) {
            throw ValidationException::withMessages([
                'verification_template' => 'This question does not belong to the verification request template.',
            ]);
        }

        return $question;
    }

    protected function normalizeAnswerValue(VerificationFormQuestion $question, mixed $value): ?string
    {
        if (is_array($value)) {
            $values = array_values(array_filter($value, fn ($item): bool => filled($item)));
            $this->validateSelectOptions($question, $values);

            return $values === [] ? null : json_encode($values);
        }

        if ($this->isBlank($value)) {
            return null;
        }

        $value = trim((string) $value);

        match ($question->input_type) {
            'date' => $this->validateDate($value),
            'number', 'currency', 'percent' => $this->validateNumeric($value, $question->input_type),
            'yes_no' => $this->validateControlledValue($value, ['yes', 'no']),
            'select' => $this->validateSelectOptions($question, [$value]),
            default => null,
        };

        return $value;
    }

    protected function validateDate(string $value): void
    {
        if (strtotime($value) === false) {
            throw ValidationException::withMessages([
                'answer' => 'Enter a valid date.',
            ]);
        }
    }

    protected function validateNumeric(string $value, string $type): void
    {
        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                'answer' => "Enter a valid {$type} value.",
            ]);
        }
    }

    protected function validateControlledValue(string $value, array $allowed): void
    {
        if (! in_array($value, $allowed, true)) {
            throw ValidationException::withMessages([
                'answer' => 'Select a valid answer.',
            ]);
        }
    }

    protected function validateSelectOptions(VerificationFormQuestion $question, array $values): void
    {
        if ($values === []) {
            return;
        }

        $allowed = $question->getSelectOptionValues();

        if ($allowed === []) {
            return;
        }

        foreach ($values as $value) {
            if (! in_array((string) $value, $allowed, true)) {
                throw ValidationException::withMessages([
                    'answer' => 'Select a valid option from the request template.',
                ]);
            }
        }
    }

    protected function isBlank(mixed $value): bool
    {
        return blank($value) && $value !== '0' && $value !== 0;
    }
}
