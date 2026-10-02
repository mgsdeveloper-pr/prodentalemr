<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Support\VerificationTemplateVersionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefreshVerificationTemplateAction
{
    public function __construct(
        protected VerificationTemplateVersionService $templateVersions,
    ) {
    }

    public function isAlreadyCurrent(BillingWorkItem $workItem): bool
    {
        return $this->templateVersions->workItemUsesLatestPublishedVersion($workItem);
    }

    public function canRefresh(BillingWorkItem $workItem, ?User $user): bool
    {
        if (! $user || ! $workItem->verificationUserCanEditVerification($user)) {
            return false;
        }

        if ($workItem->normalized_status === BillingWorkItem::STATUS_DONE) {
            return false;
        }

        try {
            return ! $this->isAlreadyCurrent($workItem);
        } catch (\Illuminate\Validation\ValidationException) {
            // Historical requests remain usable when no replacement form is active.
            return false;
        }
    }

    public function review(BillingWorkItem $workItem): array
    {
        $target = $this->templateVersions->latestPublishedVersionForWorkItem($workItem);
        $snapshot = $this->templateVersions->snapshot($target);
        $old = collect(data_get($workItem->verification_template_snapshot, 'questions', []));
        $new = collect($snapshot['questions']);
        $identity = fn (array $q) => ($q['semantic_key'] ?? null) ?: 'question:'.$q['id'];
        $fields = ['input_type', 'question_kind', 'code', 'code_system', 'field_key', 'secondary_field_key', 'secondary_input_type', 'select_options', 'answer_layout', 'response_category', 'frequency_response_fields', 'frequency_response_mode', 'parent_question_id', 'trigger_answer', 'has_note'];
        $pairs = []; $changes = []; $blocked = [];
        foreach ($old as $question) {
            $matches = $new->filter(fn ($q) => $identity($q) === $identity($question));
            if ($matches->count() > 1 || $old->filter(fn ($q) => $identity($q) === $identity($question))->count() > 1) {
                $blocked[] = $question['prompt'].' (ambiguous identity)';
                continue;
            }
            if ($matches->isEmpty()) {
                $changes[] = 'Removed or unmatched: '.$question['prompt'];
                continue;
            }
            $match = $matches->first();
            $compatible = true;
            foreach ($fields as $field) {
                if ($field === 'parent_question_id') {
                    $a = $old->firstWhere('id', $question[$field] ?? null);
                    $b = $new->firstWhere('id', $match[$field] ?? null);
                    if (($a ? $identity($a) : null) !== ($b ? $identity($b) : null)) $compatible = false;
                } elseif (($question[$field] ?? null) !== ($match[$field] ?? null)) {
                    $compatible = false;
                }
            }
            if (! $compatible) $blocked[] = $question['prompt'];
            else $pairs[$question['id']] = $match['id'];
            if ($question['prompt'] !== $match['prompt']) $changes[] = 'Renamed: '.$question['prompt'].' → '.$match['prompt'];
        }
        $oldKeys = $old->map($identity);
        foreach ($new as $question) {
            if (! $oldKeys->contains($identity($question))) {
                $changes[] = 'Added: '.$question['prompt'];
                $mappedFields = array_filter([$question['field_key'] ?? null, $question['secondary_field_key'] ?? null]);
                if ($old->contains(fn ($q) => array_intersect($mappedFields, array_filter([$q['field_key'] ?? null, $q['secondary_field_key'] ?? null])) !== [])) {
                    $blocked[] = $question['prompt'].' (reused answer field)';
                }
                if (filled($question['code'] ?? null) && $old->contains(fn ($q) => ($q['code'] ?? null) === $question['code'])) {
                    $blocked[] = $question['prompt'].' (procedure code needs identity review)';
                }
            }
        }
        $answers = $workItem->verificationFormAnswers()->orderBy('id')->get()->toArray();
        return [
            'from' => data_get($workItem->verification_template_snapshot, 'version.name').' v'.data_get($workItem->verification_template_snapshot, 'version.version_number'),
            'to' => $target->name.' v'.$target->version_number,
            'changes' => $changes, 'blocked' => $blocked, 'pairs' => $pairs,
            'token' => hash('sha256', json_encode([$workItem->getAttributes(), $snapshot, $answers, $workItem->verificationProfile()->first()?->toArray(), $workItem->verificationCoverageCodes()->get()->toArray()])),
        ];
    }

    public function execute(BillingWorkItem $workItem, ?string $reviewToken = null): BillingWorkItem
    {
        return DB::transaction(function () use ($workItem, $reviewToken) {
            $workItem = BillingWorkItem::query()->lockForUpdate()->findOrFail($workItem->id);
            if ($workItem->normalized_status === BillingWorkItem::STATUS_DONE) {
                throw new AuthorizationException('Completed requests keep their original template.');
            }
            if (auth()->user() && ! $workItem->verificationUserCanEditVerification(auth()->user())) throw new AuthorizationException();
            // Activation also locks the clinic, keeping selection stable through this transaction.
            $workItem->clinic()->lockForUpdate()->first();
            $review = $this->review($workItem);
            if ($reviewToken !== null && ! hash_equals($review['token'], $reviewToken)) {
                throw ValidationException::withMessages(['templateRefresh' => 'The request or active template changed. Cancel and review again.']);
            }
            if ($review['blocked']) throw ValidationException::withMessages(['templateRefresh' => 'Answer definitions changed. Correct their mappings before refreshing: '.implode(', ', $review['blocked'])]);
            $answers = $workItem->verificationFormAnswers()->with('question')->get();
            $history = $workItem->formSubmissions()->create([
                'user_id' => auth()->id(), 'panel' => 'verification', 'status' => $workItem->status,
                'outcome_status' => $workItem->outcome_status, 'priority' => $workItem->priority,
                'version' => ((int) $workItem->formSubmissions()->max('version')) + 1,
                'payload' => [
                    'reason' => 'before_template_refresh', 'template_snapshot' => $workItem->verification_template_snapshot,
                    'work_item' => $workItem->getAttributes(),
                    'verification_profile' => $workItem->verificationProfile?->toArray(),
                    'coverage_codes' => $workItem->verificationCoverageCodes->toArray(),
                    'answers' => $answers->map(fn ($a) => ['question_id' => $a->verification_form_question_id, 'prompt' => $a->question?->prompt, 'answer_value' => $a->answer_value, 'note_value' => $a->note_value])->all(),
                ],
            ]);
            foreach ($answers as $answer) {
                $newId = $review['pairs'][$answer->verification_form_question_id] ?? null;
                if ($newId && $newId !== $answer->verification_form_question_id) {
                    $workItem->verificationFormAnswers()->updateOrCreate(['verification_form_question_id' => $newId], $answer->only(['answer_value', 'note_value']));
                }
            }
            $workItem = $this->templateVersions->refreshWorkItemSnapshot($workItem);
            $workItem->recordActivity('template_refreshed', 'Template refreshed; previous answers preserved in submission history.', [
                'template_version_id' => $workItem->verification_template_version_id, 'submission_id' => $history->id,
                'status_preserved' => $workItem->status,
            ]);
            return $workItem;
        });
    }
}
