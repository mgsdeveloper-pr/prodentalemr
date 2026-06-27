<?php

namespace App\Filament\Clinic\Resources\VerificationQuestions\Pages;

use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Filament\Clinic\Resources\VerificationQuestions\Pages\Concerns\InteractsWithVerificationQuestionOrdering;
use App\Models\VerificationFormQuestion;
use App\Support\ClinicPanelScope;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateVerificationQuestion extends CreateRecord
{
    use InteractsWithVerificationQuestionOrdering;

    protected static string $resource = VerificationQuestionResource::class;

    protected string $view = 'filament.clinic.resources.verification-questions.pages.verification-question-editor';

    protected Width | string | null $maxContentWidth = Width::Full;

    public function getSelectedClinicName(): string
    {
        return ClinicPanelScope::selectedClinic()?->clinic_name ?? 'Select clinic scope';
    }

    public function getSectionCards(): array
    {
        return collect(VerificationFormQuestion::sectionOptionsForTemplate($this->data['template_key'] ?? 'template_2', ClinicPanelScope::selectedClinicId()))
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

        return filled($key)
            ? str_replace(' Snapshot', '', VerificationFormQuestion::sectionLabel($key, $this->data['template_key'] ?? 'template_2', ClinicPanelScope::selectedClinicId()))
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
        return 'create';
    }

    public function getSubmitButtonLabel(): string
    {
        return 'Create question';
    }

    public function getCancelUrl(): string
    {
        return $this->previousUrl ?: VerificationQuestionResource::getUrl();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinic_id'] = $data['clinic_id'] ?: ClinicPanelScope::selectedClinicId();
        $data['organization_id'] = $data['organization_id'] ?: ClinicPanelScope::selectedOrganizationId();
        $data['sort_order'] = (int) ($data['sort_order'] ?? 9990);
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

    protected function afterCreate(): void
    {
        /** @var VerificationFormQuestion $record */
        $record = $this->getRecord();

        $this->reorderSectionQuestions(
            $record,
            $this->data['order_position'] ?? 'bottom',
            filled($this->data['order_reference_id'] ?? null) ? (int) $this->data['order_reference_id'] : null,
        );
    }

    protected function getRedirectUrl(): string
    {
        return VerificationQuestionResource::getUrl('create');
    }
}
