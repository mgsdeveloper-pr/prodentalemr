<?php

namespace App\Filament\Saas\Resources\VerificationFormQuestions\Pages\Concerns;

use App\Models\VerificationFormQuestion;

trait InteractsWithVerificationFormQuestionOrdering
{
    protected function resolveOrderingTemplateVersionId(): ?int
    {
        if (property_exists($this, 'templateVersionId') && filled($this->templateVersionId)) {
            return (int) $this->templateVersionId;
        }

        if (method_exists($this, 'getRecord') && $this->getRecord()) {
            return filled($this->getRecord()->template_version_id)
                ? (int) $this->getRecord()->template_version_id
                : null;
        }

        return null;
    }

    protected function resolveOrderingClinicId(): ?int
    {
        $clinicId = $this->data['clinic_id'] ?? null;

        if (filled($clinicId)) {
            return (int) $clinicId;
        }

        if (method_exists($this, 'getRecord') && $this->getRecord()) {
            return filled($this->getRecord()->clinic_id) ? (int) $this->getRecord()->clinic_id : null;
        }

        return null;
    }

    protected function resolveOrderingOrganizationId(): ?int
    {
        $organizationId = $this->data['organization_id'] ?? null;

        if (filled($organizationId)) {
            return (int) $organizationId;
        }

        if (method_exists($this, 'getRecord') && $this->getRecord()) {
            return filled($this->getRecord()->organization_id) ? (int) $this->getRecord()->organization_id : null;
        }

        return null;
    }

    public function getSectionQuestionOrderCards(): array
    {
        $clinicId = $this->resolveOrderingClinicId();
        $organizationId = $this->resolveOrderingOrganizationId();
        $sectionKey = $this->data['sub_section_key'] ?? $this->data['section_key'] ?? null;
        $templateKey = $this->data['template_key'] ?? VerificationFormQuestion::defaultTemplateKey();
        $templateVersionId = $this->resolveOrderingTemplateVersionId();

        if (! filled($sectionKey)) {
            return [];
        }

        $sectionKeys = [$sectionKey];

        if (($this->data['sub_section_key'] ?? null) === null) {
            $childSectionKeys = array_keys(
                VerificationFormQuestion::childSectionOptionsForTemplate($templateKey, $clinicId, $sectionKey, $templateVersionId)
            );

            $sectionKeys = array_values(array_unique([...$sectionKeys, ...$childSectionKeys]));
        }

        $recordId = method_exists($this, 'getRecord') && $this->getRecord()
            ? $this->getRecord()->getKey()
            : null;

        return VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->when($templateVersionId, fn ($query) => $query->where('template_version_id', $templateVersionId))
            ->where('template_key', $templateKey)
            ->whereIn('section_key', $sectionKeys)
            ->where('is_active', true)
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

    public function reorderExistingSectionQuestions(array $orderedIds): void
    {
        $orderedIds = collect($orderedIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($orderedIds->isEmpty()) {
            return;
        }

        $clinicId = $this->resolveOrderingClinicId();
        $organizationId = $this->resolveOrderingOrganizationId();
        $sectionKey = $this->data['sub_section_key'] ?? $this->data['section_key'] ?? null;
        $templateKey = $this->data['template_key'] ?? VerificationFormQuestion::defaultTemplateKey();
        $templateVersionId = $this->resolveOrderingTemplateVersionId();

        if (! filled($sectionKey)) {
            return;
        }

        $sectionKeys = [$sectionKey];

        if (($this->data['sub_section_key'] ?? null) === null) {
            $childSectionKeys = array_keys(
                VerificationFormQuestion::childSectionOptionsForTemplate($templateKey, $clinicId, $sectionKey, $templateVersionId)
            );

            $sectionKeys = array_values(array_unique([...$sectionKeys, ...$childSectionKeys]));
        }

        $questions = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->when($templateVersionId, fn ($query) => $query->where('template_version_id', $templateVersionId))
            ->where('template_key', $templateKey)
            ->whereIn('section_key', $sectionKeys)
            ->where('is_active', true)
            ->whereIn('id', $orderedIds)
            ->get()
            ->keyBy('id');

        foreach ($orderedIds as $index => $questionId) {
            $question = $questions->get($questionId);

            if (! $question) {
                continue;
            }

            $question->forceFill([
                'sort_order' => ($index + 1) * 10,
            ])->saveQuietly();
        }
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
        $clinicId = $record->clinic_id ?: $this->resolveOrderingClinicId();
        $organizationId = $record->organization_id ?: $this->resolveOrderingOrganizationId();
        $sectionKey = $record->section_key;
        $templateKey = $record->template_key;

        if (! filled($sectionKey)) {
            return;
        }

        $questions = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->when($record->template_version_id, fn ($query) => $query->where('template_version_id', $record->template_version_id))
            ->where('template_key', $templateKey)
            ->where('section_key', $sectionKey)
            ->where('is_active', true)
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

    protected function normalizeSectionQuestionOrder(?string $sectionKey, ?int $excludeRecordId = null, ?int $clinicId = null): void
    {
        $clinicId = $clinicId ?: $this->resolveOrderingClinicId();
        $organizationId = $this->resolveOrderingOrganizationId();
        $templateVersionId = $this->resolveOrderingTemplateVersionId();

        if (! filled($sectionKey)) {
            return;
        }

        $questions = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->when($templateVersionId, fn ($query) => $query->where('template_version_id', $templateVersionId))
            ->where('template_key', $this->data['template_key'] ?? VerificationFormQuestion::defaultTemplateKey())
            ->where('section_key', $sectionKey)
            ->where('is_active', true)
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
