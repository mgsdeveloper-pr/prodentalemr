<?php

namespace App\Filament\Saas\Resources\VerificationFormQuestions\Pages;

use App\Filament\Saas\Resources\VerificationFormQuestions\Pages\Concerns\InteractsWithVerificationFormQuestionOrdering;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateVersion;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateVerificationFormQuestion extends CreateRecord
{
    use InteractsWithVerificationFormQuestionOrdering;

    protected static string $resource = VerificationFormQuestionResource::class;

    protected string $view = 'filament.saas.resources.verification-form-questions.pages.verification-form-question-editor';

    protected Width|string|null $maxContentWidth = Width::Full;

    public ?int $templateVersionId = null;

    public function mount(): void
    {
        parent::mount();

        $this->templateVersionId = request()->integer('version') ?: null;
        $version = $this->resolveTargetVersion();

        abort_unless($version, 404, 'Master Template draft not found.');

        $sectionKey = request()->query('section');

        if (! is_string($sectionKey) || blank($sectionKey)) {
            return;
        }

        $templateKey = VerificationFormQuestion::defaultTemplateKey();
        $section = $version->sections()
            ->where('template_key', $templateKey)
            ->where('section_key', $sectionKey)
            ->first();

        abort_unless($section, 404, 'Template section not found in this draft.');

        $parentSectionKey = $section->parent_section_key;

        $this->data['template_key'] = $templateKey;
        $this->data['template_version_id'] = $version->getKey();

        if ($parentSectionKey) {
            $this->data['section_key'] = $parentSectionKey;
            $this->data['sub_section_key'] = $sectionKey;
        } else {
            $this->data['section_key'] = $sectionKey;
            $this->data['sub_section_key'] = null;
        }

        $this->form->fill($this->data);
    }

    public function getSelectedClinicName(): string
    {
        return 'Platform Master Template';
    }

    public function getSectionCards(): array
    {
        return collect(VerificationFormQuestion::sectionOptionsForTemplate(
            $this->data['template_key'] ?? VerificationFormQuestion::defaultTemplateKey(),
            null,
            $this->templateVersionId,
        ))
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
            ? str_replace(' Snapshot', '', VerificationFormQuestion::sectionLabel($key, $this->data['template_key'] ?? VerificationFormQuestion::defaultTemplateKey(), null))
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
        return VerificationFormQuestionResource::getUrl(parameters: ['version' => $this->templateVersionId]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $version = $this->resolveTargetVersion();

        abort_unless($version, 404, 'Master Template draft not found.');

        $data['template_version_id'] = $version->getKey();
        $data['organization_id'] = null;
        $data['clinic_id'] = null;

        $data['sort_order'] = (int) ($data['sort_order'] ?? 9990);
        $data['section_key'] = filled($data['sub_section_key'] ?? null)
            ? $data['sub_section_key']
            : $data['section_key'];
        unset($data['sub_section_key']);

        if (VerificationFormQuestion::isFrequencyPercentageSection($data['section_key'] ?? null)) {
            $data['input_type'] = 'frequency_row';
            $data['question_kind'] = VerificationFormQuestion::QUESTION_KIND_NORMAL;
            $data['parent_question_id'] = null;
            $data['trigger_answer'] = null;
            $data['code'] = filled($data['code'] ?? null) ? $data['code'] : null;
            $data['frequency_response_mode'] = $data['frequency_response_mode'] ?: 'current';
            $data['frequency_response_fields'] = $data['frequency_response_fields'] ?: VerificationFormQuestion::defaultFrequencyResponseFields($data['frequency_response_mode']);
        } else {
            $data['question_kind'] = $data['question_kind'] ?? VerificationFormQuestion::QUESTION_KIND_NORMAL;

            if ($data['question_kind'] !== VerificationFormQuestion::QUESTION_KIND_CONDITIONAL) {
                $data['parent_question_id'] = null;
                $data['trigger_answer'] = null;
            }

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
        return VerificationFormQuestionResource::getUrl(parameters: ['version' => $this->templateVersionId]);
    }

    protected function resolveTargetVersion(): ?VerificationTemplateVersion
    {
        if (! $this->templateVersionId) {
            return null;
        }

        $version = VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->whereKey($this->templateVersionId)
            ->first();

        return $version?->canEditDirectly() ? $version : null;
    }
}
