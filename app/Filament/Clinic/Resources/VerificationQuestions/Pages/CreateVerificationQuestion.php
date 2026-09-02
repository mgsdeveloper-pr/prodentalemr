<?php

namespace App\Filament\Clinic\Resources\VerificationQuestions\Pages;

use App\Filament\Clinic\Resources\VerificationQuestions\Pages\Concerns\InteractsWithVerificationQuestionOrdering;
use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Models\VerificationFormQuestion;
use App\Support\ClinicPanelScope;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Url;

class CreateVerificationQuestion extends CreateRecord
{
    use InteractsWithVerificationQuestionOrdering;

    protected static string $resource = VerificationQuestionResource::class;

    protected string $view = 'filament.clinic.resources.verification-questions.pages.verification-question-editor';

    protected Width|string|null $maxContentWidth = Width::Full;

    #[Url(as: 'section')]
    public ?string $requestedSectionKey = null;

    #[Url(as: 'template_version_id')]
    public ?int $requestedTemplateVersionId = null;

    public function mount(): void
    {
        parent::mount();

        $this->applyRequestedSection();
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $version = VerificationQuestionResource::currentClinicWorkingVersion(
            ClinicPanelScope::selectedClinic(),
            $this->requestedTemplateVersionId,
        );

        abort_unless(
            filled($this->requestedTemplateVersionId)
                && (int) $version?->getKey() === (int) $this->requestedTemplateVersionId
                && $version?->canEditDirectly(),
            404,
        );
    }

    public function getTitle(): string
    {
        return 'Add Question';
    }

    public function getHeading(): string
    {
        return 'Add Question';
    }

    public function getBreadcrumb(): string
    {
        return 'Add Question';
    }

    public function getSelectedClinicName(): string
    {
        return ClinicPanelScope::selectedClinic()?->clinic_name ?? 'Select clinic scope';
    }

    public function getSectionCards(): array
    {
        return collect(VerificationFormQuestion::sectionOptionsForTemplate($this->data['template_key'] ?? VerificationFormQuestion::defaultTemplateKey(), ClinicPanelScope::selectedClinicId()))
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
            ? str_replace(' Snapshot', '', VerificationFormQuestion::sectionLabel($key, $this->data['template_key'] ?? VerificationFormQuestion::defaultTemplateKey(), ClinicPanelScope::selectedClinicId()))
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
        return $this->getBuilderReturnUrl();
    }

    public function getSecondarySubmitMethodName(): string
    {
        return 'createAnother';
    }

    public function getSecondarySubmitButtonLabel(): string
    {
        return 'Save & Add Another';
    }

    protected function afterFill(): void
    {
        $this->applyRequestedSection();
    }

    protected function applyRequestedSection(): void
    {
        $sectionKey = trim((string) ($this->requestedSectionKey ?: request()->query('section', '')));
        $templateKey = VerificationFormQuestion::defaultTemplateKey();
        $sectionOptions = VerificationFormQuestion::sectionOptionsForTemplate($templateKey, ClinicPanelScope::selectedClinicId());

        if ($sectionKey === '' || ! array_key_exists($sectionKey, $sectionOptions)) {
            return;
        }

        $parentSectionKey = VerificationFormQuestion::parentSectionKeyFor(
            $sectionKey,
            $templateKey,
            ClinicPanelScope::selectedClinicId(),
        );

        $this->data['template_key'] = $templateKey;
        $this->data['section_key'] = $parentSectionKey ?: $sectionKey;
        $this->data['sub_section_key'] = $parentSectionKey ? $sectionKey : null;

        if (VerificationFormQuestion::isFrequencyPercentageSection($sectionKey)) {
            $this->data['input_type'] = 'frequency_row';
            $this->data['frequency_row_mode'] ??= 'question';
            $this->data['frequency_response_mode'] ??= 'current';
            $this->data['frequency_response_fields'] ??= VerificationFormQuestion::defaultFrequencyResponseFields('current');
        } elseif (($this->data['input_type'] ?? null) === 'frequency_row') {
            $this->data['input_type'] = 'text';
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['template_key'] = $data['template_key'] ?: VerificationFormQuestion::defaultTemplateKey();
        $data['clinic_id'] = $data['clinic_id'] ?: ClinicPanelScope::selectedClinicId();
        $data['organization_id'] = $data['organization_id'] ?: ClinicPanelScope::selectedOrganizationId();
        $version = VerificationQuestionResource::currentClinicWorkingVersion(
            ClinicPanelScope::selectedClinic(),
            $this->requestedTemplateVersionId,
        );

        abort_unless($version?->canEditDirectly(), 403, 'Only an unused clinic template draft can be changed.');
        $data['template_version_id'] = $version->getKey();
        $data['sort_order'] = (int) ($data['sort_order'] ?? 9990);
        $data['section_key'] = filled($data['sub_section_key'] ?? null)
            ? $data['sub_section_key']
            : $data['section_key'];
        unset($data['sub_section_key']);

        if (VerificationFormQuestion::isFrequencyPercentageSection($data['section_key'] ?? null)) {
            $data['input_type'] = 'frequency_row';
            $data['code'] = filled($data['code'] ?? null) ? $data['code'] : null;
            $data['frequency_response_mode'] = $data['frequency_response_mode'] ?: 'current';
            $data['frequency_response_fields'] = VerificationFormQuestion::normalizeFrequencyResponseFields(
                $data['frequency_response_fields'] ?? null,
                $data['frequency_response_mode'],
            );
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

        Notification::make()
            ->title('Question created')
            ->body('The question was added to this clinic template.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getBuilderReturnUrl($this->getRecord());
    }

    protected function preserveFormDataWhenCreatingAnother(array $data): array
    {
        return collect($data)
            ->only([
                'organization_id',
                'clinic_id',
                'template_key',
                'section_key',
                'sub_section_key',
                'form_type',
            ])
            ->merge([
                'order_position' => 'bottom',
                'order_reference_id' => null,
            ])
            ->all();
    }

    protected function getBuilderReturnUrl(?VerificationFormQuestion $record = null): string
    {
        $versionId = $record?->template_version_id ?: $this->requestedTemplateVersionId;
        $sectionKey = $record?->section_key
            ?: ($this->data['sub_section_key'] ?? $this->data['section_key'] ?? $this->requestedSectionKey);

        return VerificationQuestionResource::getUrl('index', array_filter([
            'draft' => $versionId ? '1' : null,
            'version' => $versionId,
            'section' => $sectionKey,
        ]));
    }
}
