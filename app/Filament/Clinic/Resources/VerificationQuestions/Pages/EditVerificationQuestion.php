<?php

namespace App\Filament\Clinic\Resources\VerificationQuestions\Pages;

use App\Filament\Clinic\Resources\VerificationQuestions\Pages\Concerns\InteractsWithVerificationQuestionOrdering;
use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Models\VerificationFormQuestion;
use App\Support\ClinicPanelScope;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Url;

class EditVerificationQuestion extends EditRecord
{
    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        if (! $this->getRecord()->is_builtin) {
            return parent::form($schema);
        }

        return $schema->columns(1)->components([
            \Filament\Forms\Components\TextInput::make('prompt')->label('Question')->required()->maxLength(255),
            \Filament\Forms\Components\Textarea::make('help_text')->label('Help text')->maxLength(2000),
            \Filament\Forms\Components\TextInput::make('placeholder')->label('Placeholder')->maxLength(255),
            \Filament\Forms\Components\Select::make('input_type')->label('Answer type')
                ->options(VerificationFormQuestion::INPUT_TYPE_OPTIONS)->disabled()->dehydrated(false)
                ->helperText('Protected because this question is connected to patient or verification data.'),
        ]);
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
            // Serialize against publication and recheck lifecycle and clinic access at save time.
            $version = \App\Models\VerificationTemplateVersion::query()->lockForUpdate()->findOrFail($record->template_version_id);
            $fresh = VerificationFormQuestion::query()->lockForUpdate()->findOrFail($record->getKey());
            $fresh->setRelation('templateVersion', $version);
            abort_unless(VerificationQuestionResource::canEdit($fresh), 403);
            abort_unless((int) $fresh->template_version_id === (int) $this->requestedTemplateVersionId, 404);
            if ($fresh->is_builtin) {
                $data = \Illuminate\Support\Arr::only($data, ['prompt', 'help_text', 'placeholder']);
            }
            $fresh->update($data);
            return $fresh;
        });
    }
    #[\Livewire\Attributes\Url(as: 'form')]
    public string $builderFormType = 'full_form';
    use InteractsWithVerificationQuestionOrdering;

    protected static string $resource = VerificationQuestionResource::class;

    protected string $view = 'filament.clinic.resources.verification-questions.pages.verification-question-editor';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected ?string $originalSectionKey = null;

    #[Url(as: 'section')]
    public ?string $requestedSectionKey = null;

    #[Url(as: 'template_version_id')]
    public ?int $requestedTemplateVersionId = null;

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        abort_unless(
            (int) $this->getRecord()->clinic_id === (int) ClinicPanelScope::selectedClinicId()
                && filled($this->requestedTemplateVersionId)
                && (int) $this->getRecord()->template_version_id === (int) $this->requestedTemplateVersionId,
            404,
        );
    }

    public function getSelectedClinicName(): string
    {
        return ClinicPanelScope::selectedClinic()?->clinic_name ?? 'Select clinic scope';
    }

    public function getSectionCards(): array
    {
        return collect(VerificationFormQuestion::sectionOptionsForTemplate($this->data['template_key'] ?? $this->record?->template_key ?? VerificationFormQuestion::defaultTemplateKey(), ClinicPanelScope::selectedClinicId()))
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
            ? str_replace(' Snapshot', '', VerificationFormQuestion::sectionLabel($key, $this->data['template_key'] ?? $this->record?->template_key ?? VerificationFormQuestion::defaultTemplateKey(), ClinicPanelScope::selectedClinicId()))
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
        return $this->getBuilderReturnUrl();
    }

    public function getSecondarySubmitMethodName(): string
    {
        return 'saveAndAddAnother';
    }

    public function getSecondarySubmitButtonLabel(): string
    {
        return 'Save & Add Another';
    }

    public function saveAndAddAnother(): void
    {
        $this->save(shouldRedirect: false);

        $this->redirect(VerificationQuestionResource::getUrl('create', [
            'section' => $this->getRecord()->section_key,
            'template_version_id' => $this->getRecord()->template_version_id,
        ]), navigate: true);
    }

    protected function afterFill(): void
    {
        $this->originalSectionKey = $this->record?->section_key;
        if ($this->record?->input_type === 'frequency_row') {
            $this->data['answer_layout'] = $this->record->answer_layout ?: $this->record->inferredAnswerLayout();
        }

        $parentSectionKey = VerificationFormQuestion::parentSectionKeyFor(
            $this->record?->section_key,
            $this->record?->template_key,
            ClinicPanelScope::selectedClinicId(),
            $this->record?->template_version_id,
        );

        if (filled($parentSectionKey)) {
            $this->data['section_key'] = $parentSectionKey;
            $this->data['sub_section_key'] = $this->record?->section_key;
        }

        if ($this->record?->input_type === 'frequency_row' || VerificationFormQuestion::isFrequencyPercentageSection($this->record?->section_key)) {
            $this->data['frequency_row_mode'] = filled($this->record?->code) ? 'code' : 'question';
            $this->data['frequency_response_mode'] = $this->record?->frequency_response_mode ?: 'current';
            $this->data['frequency_response_fields'] = VerificationFormQuestion::normalizeFrequencyResponseFields(
                $this->record?->frequency_response_fields,
                $this->data['frequency_response_mode'],
            );
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->getRecord()->is_builtin) {
            return \Illuminate\Support\Arr::only($data, ['prompt', 'help_text', 'placeholder']);
        }
        $data['sort_order'] = (int) ($data['sort_order'] ?? ($this->record?->sort_order ?? 9990));
        $data['section_key'] = filled($data['sub_section_key'] ?? null)
            ? $data['sub_section_key']
            : $data['section_key'];
        unset($data['sub_section_key']);

        if (($data['input_type'] ?? null) === 'frequency_row' || VerificationFormQuestion::isFrequencyPercentageSection($data['section_key'] ?? null)) {
            $data['input_type'] = 'frequency_row';
            $data['answer_layout'] = $data['answer_layout'] ?? (new VerificationFormQuestion($data))->inferredAnswerLayout();
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

    protected function afterSave(): void
    {
        if ($this->getRecord()->is_builtin) {
            return;
        }
        /** @var VerificationFormQuestion $record */
        $record = $this->getRecord();

        if ($this->originalSectionKey && $this->originalSectionKey !== $record->section_key) {
            $this->normalizeSectionQuestionOrder($this->originalSectionKey, $record->getKey());
        }

        $this->reorderSectionQuestions(
            $record,
            $this->data['order_position'] ?? 'bottom',
            filled($this->data['order_reference_id'] ?? null) ? (int) $this->data['order_reference_id'] : null,
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getBuilderReturnUrl();
    }

    protected function getBuilderReturnUrl(): string
    {
        return VerificationQuestionResource::getUrl('index', [
            'draft' => '1',
            'form' => in_array($this->builderFormType, ['full_form', 'short_form'], true) ? $this->builderFormType : 'full_form',
            'version' => $this->getRecord()->template_version_id,
            'section' => $this->getRecord()->section_key,
        ]);
    }
}
