<?php

namespace App\Filament\Saas\Resources\VerificationFormQuestions\Pages;

use App\Filament\Saas\Resources\VerificationFormQuestions\Pages\Concerns\InteractsWithVerificationFormQuestionOrdering;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Models\Clinic;
use App\Models\VerificationFormQuestion;
use App\Support\AdminClinicScope;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateVerificationFormQuestion extends CreateRecord
{
    use InteractsWithVerificationFormQuestionOrdering;

    protected static string $resource = VerificationFormQuestionResource::class;

    protected string $view = 'filament.saas.resources.verification-form-questions.pages.verification-form-question-editor';

    protected Width | string | null $maxContentWidth = Width::Full;

    public function getSelectedClinicName(): string
    {
        $clinicId = $this->data['clinic_id'] ?? null;

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
        return collect(VerificationFormQuestion::sectionOptionsForTemplate($this->data['template_key'] ?? 'template_2'))
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => str_replace(' Snapshot', '', $label),
            ])
            ->values()
            ->all();
    }

    public function getCurrentSectionLabel(): string
    {
        $key = $this->data['section_key'] ?? null;

        return filled($key)
            ? str_replace(' Snapshot', '', VerificationFormQuestion::SECTION_OPTIONS[$key]
                ?? VerificationFormQuestion::TEMPLATE_2_SECTION_OPTIONS[$key]
                ?? (string) $key)
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
        return $this->previousUrl ?: VerificationFormQuestionResource::getUrl();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinic_id'] = $data['clinic_id'] ?: AdminClinicScope::selectedClinicId();

        if (filled($data['clinic_id']) && ! filled($data['organization_id'])) {
            $data['organization_id'] = Clinic::query()->whereKey($data['clinic_id'])->value('organization_id');
        }

        $data['sort_order'] = (int) ($data['sort_order'] ?? 9990);

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
}
