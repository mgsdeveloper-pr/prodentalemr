<?php

namespace App\Filament\Clinic\Resources\VerificationQuestions\Pages\Concerns;

use App\Models\VerificationFormQuestion;
use App\Support\ClinicPanelScope;

trait InteractsWithVerificationQuestionOrdering
{
    public function getSectionQuestionOrderCards(): array
    {
        $clinicId = ClinicPanelScope::selectedClinicId();
        $sectionKey = $this->data['section_key'] ?? null;
        $templateKey = $this->data['template_key'] ?? 'template_1';

        if (! $clinicId || ! filled($sectionKey)) {
            return [];
        }

        $recordId = method_exists($this, 'getRecord') && $this->getRecord()
            ? $this->getRecord()->getKey()
            : null;

        return VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->where('template_key', $templateKey)
            ->where('section_key', $sectionKey)
            ->when($recordId, fn ($query) => $query->whereKeyNot($recordId))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (VerificationFormQuestion $question): array => [
                'id' => $question->getKey(),
                'prompt' => $question->prompt,
                'sort_order' => $question->sort_order,
            ])
            ->all();
    }

    public function setPlacement(string $mode, ?int $referenceId = null): void
    {
        $this->data['order_position'] = $mode;
        $this->data['order_reference_id'] = $referenceId;
    }

    public function getPlacementSummaryLabel(): string
    {
        $mode = $this->data['order_position'] ?? 'bottom';
        $referenceId = $this->data['order_reference_id'] ?? null;

        return match ($mode) {
            'top' => 'This question will be placed at the top of the selected section.',
            'above' => $referenceId ? 'This question will be placed above the selected question.' : 'Choose a reference question.',
            'below' => $referenceId ? 'This question will be placed below the selected question.' : 'Choose a reference question.',
            default => 'This question will be placed at the bottom of the selected section.',
        };
    }

    protected function stripOrderingMeta(array $data): array
    {
        unset($data['order_position'], $data['order_reference_id']);

        return $data;
    }

    protected function reorderSectionQuestions(VerificationFormQuestion $record, ?string $mode = null, ?int $referenceId = null): void
    {
        $clinicId = $record->clinic_id;
        $sectionKey = $record->section_key;
        $templateKey = $record->template_key;

        if (! $clinicId || ! filled($sectionKey)) {
            return;
        }

        $questions = VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->where('template_key', $templateKey)
            ->where('section_key', $sectionKey)
            ->whereKeyNot($record->getKey())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $ordered = $questions->all();

        $mode = $mode ?: 'bottom';

        if ($mode === 'top') {
            array_unshift($ordered, $record);
        } elseif (in_array($mode, ['above', 'below'], true) && $referenceId) {
            $inserted = false;

            foreach ($ordered as $index => $question) {
                if ($question->getKey() !== $referenceId) {
                    continue;
                }

                $insertAt = $mode === 'above' ? $index : $index + 1;
                array_splice($ordered, $insertAt, 0, [$record]);
                $inserted = true;

                break;
            }

            if (! $inserted) {
                $ordered[] = $record;
            }
        } else {
            $ordered[] = $record;
        }

        foreach (array_values($ordered) as $index => $question) {
            $question->forceFill([
                'sort_order' => ($index + 1) * 10,
            ])->saveQuietly();
        }
    }

    protected function normalizeSectionQuestionOrder(?string $sectionKey, ?int $excludeRecordId = null): void
    {
        $clinicId = ClinicPanelScope::selectedClinicId();

        if (! $clinicId || ! filled($sectionKey)) {
            return;
        }

        $questions = VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->where('template_key', $this->data['template_key'] ?? 'template_1')
            ->where('section_key', $sectionKey)
            ->when($excludeRecordId, fn ($query) => $query->whereKeyNot($excludeRecordId))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        foreach ($questions as $index => $question) {
            $question->forceFill([
                'sort_order' => ($index + 1) * 10,
            ])->saveQuietly();
        }
    }
}
