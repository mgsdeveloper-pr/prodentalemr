<?php

namespace App\Filament\Saas\Resources\VerificationFormQuestions\Pages;

use App\Filament\Saas\Resources\VerificationFormQuestions\Pages\Concerns\InteractsWithVerificationFormQuestionOrdering;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Models\Clinic;
use App\Models\VerificationFormQuestion;
use App\Support\AdminClinicScope;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditVerificationFormQuestion extends EditRecord
{
    use InteractsWithVerificationFormQuestionOrdering;

    protected static string $resource = VerificationFormQuestionResource::class;

    protected string $view = 'filament.saas.resources.verification-form-questions.pages.verification-form-question-editor';

    protected Width | string | null $maxContentWidth = Width::Full;

    protected ?string $originalSectionKey = null;

    public function getSelectedClinicName(): string
    {
        $clinicId = $this->data['clinic_id'] ?? $this->record?->clinic_id ?? null;

        if (filled($clinicId)) {
            $clinic = Clinic::query()->with('organization')->find($clinicId);

            if ($clinic) {
                return $clinic->clinic_name . ' - ' . ($clinic->organization?->name ?? '');
            }
        }

        $selectedClinic = AdminClinicScope::selectedClinic();

        return $selectedClinic
            ? $selectedClinic->clinic_name . ' - ' . ($selectedClinic->organization?->name ?? '')
            : 'Select clinic scope';
    }

    public function getSectionCards(): array
    {
        $clinicId = filled($this->data['clinic_id'] ?? null) ? (int) $this->data['clinic_id'] : ($this->record?->clinic_id ?? AdminClinicScope::selectedClinicId());

        return collect(VerificationFormQuestion::sectionOptionsForTemplate($this->data['template_key'] ?? $this->record?->template_key ?? 'template_2', $clinicId))
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => str_replace(' Snapshot', '', $label),
            ])
            ->values()
            ->all();
    }

    public function getCurrentSectionLabel(): string
    {
        $key = $this->data['sub_section_key'] ?? $this->data['section_key'] ?? null;
        $clinicId = filled($this->data['clinic_id'] ?? null) ? (int) $this->data['clinic_id'] : ($this->record?->clinic_id ?? AdminClinicScope::selectedClinicId());

        return filled($key)
            ? str_replace(' Snapshot', '', VerificationFormQuestion::sectionLabel($key, $this->data['template_key'] ?? $this->record?->template_key ?? 'template_2', $clinicId))
            : 'Choose section';
    }

    public function getCurrentVisibilityLabel(): string
    {
        $key = $this->data['form_type'] ?? null;

        return filled($key)
            ? VerificationFormQuestion::FORM_TYPE_OPTIONS[$key] ?? (string) $key
            : 'Choose visibility';
    }

    public function getCurrentAnswerTypeLabel(): string
    {
        $key = $this->data['input_type'] ?? null;

        return filled($key)
            ? VerificationFormQuestion::INPUT_TYPE_OPTIONS[$key] ?? (string) $key
            : 'Choose answer type';
    }

    public function getCurrentPromptPreview(): string
    {
        return filled($this->data['prompt'] ?? null)
            ? (string) $this->data['prompt']
            : 'Your drafted question will appear here as a preview.';
    }

    public function getSubmitMethodName(): string
    {
        return 'save';
    }

    public function getSubmitButtonLabel(): string
    {
        return 'Save changes';
    }

    public function getCancelUrl(): string
    {
        return $this->previousUrl ?: VerificationFormQuestionResource::getUrl();
    }

    protected function afterFill(): void
    {
        $this->originalSectionKey = $this->record?->section_key;

        $clinicId = filled($this->data['clinic_id'] ?? null) ? (int) $this->data['clinic_id'] : ($this->record?->clinic_id ?? AdminClinicScope::selectedClinicId());
        $parentSectionKey = VerificationFormQuestion::parentSectionKeyFor(
            $this->record?->section_key,
            $this->record?->template_key,
            $clinicId,
        );

        if (filled($parentSectionKey)) {
            $this->data['section_key'] = $parentSectionKey;
            $this->data['sub_section_key'] = $this->record?->section_key;
        }

        if (VerificationFormQuestion::isFrequencyPercentageSection($this->record?->section_key)) {
            $this->data['frequency_row_mode'] = filled($this->record?->code) ? 'code' : 'question';
            $this->data['frequency_response_mode'] = $this->record?->frequency_response_mode ?: 'current';
            $this->data['frequency_response_fields'] = $this->record?->frequency_response_fields ?: VerificationFormQuestion::defaultFrequencyResponseFields($this->data['frequency_response_mode']);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['sort_order'] = (int) ($data['sort_order'] ?? ($this->record?->sort_order ?? 9990));
        $data['section_key'] = filled($data['sub_section_key'] ?? null)
            ? $data['sub_section_key']
            : $data['section_key'];
        unset($data['sub_section_key']);

        if (VerificationFormQuestion::isFrequencyPercentageSection($data['section_key'] ?? null)) {
            $data['input_type'] = 'frequency_row';
            $data['code'] = filled($data['code'] ?? null) ? $data['code'] : null;
            $data['frequency_response_mode'] = $data['frequency_response_mode'] ?: 'current';
            $data['frequency_response_fields'] = $data['frequency_response_fields'] ?: VerificationFormQuestion::defaultFrequencyResponseFields($data['frequency_response_mode']);
        } else {
            $data['frequency_response_mode'] = null;
            $data['frequency_response_fields'] = null;
        }
        unset($data['frequency_row_mode']);

        return $this->stripOrderingMeta($data);
    }

    protected function afterSave(): void
    {
        /** @var VerificationFormQuestion $record */
        $record = $this->getRecord();

        if ($this->originalSectionKey && $this->originalSectionKey !== $record->section_key) {
            $this->normalizeSectionQuestionOrder($this->originalSectionKey, $record->getKey(), $record->clinic_id);
        }

        $this->reorderSectionQuestions(
            $record,
            $this->data['order_position'] ?? 'bottom',
            filled($this->data['order_reference_id'] ?? null) ? (int) $this->data['order_reference_id'] : null,
        );
    }
}
