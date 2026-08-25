<?php

namespace App\Filament\Saas\Resources\VerificationFormQuestions\Pages;

use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Models\Clinic;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateSection;
use App\Models\VerificationTemplateVersion;
use App\Support\VerificationTemplateThreeDefaults;
use App\Support\VerificationTemplateVersionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;

class ListVerificationFormQuestions extends ListRecords
{
    protected static string $resource = VerificationFormQuestionResource::class;

    protected string $view = 'filament.saas.resources.verification-form-questions.pages.list-verification-form-questions';

    protected Width|string|null $maxContentWidth = Width::Full;

    public ?int $selectedTemplateVersionId = null;

    public bool $showTemplatePreview = false;

    public string $templatePreviewFormType = 'full_form';

    public array $expandedTemplateSectionKeys = [];

    public bool $showSectionQuestionModal = false;

    public ?string $questionSectionKey = null;

    public ?string $questionSectionLabel = null;

    public array $newQuestionData = [
        'prompt' => '',
        'input_type' => 'text',
        'form_type' => 'both',
        'placeholder' => '',
        'help_text' => '',
        'select_options' => '',
    ];

    public bool $showTemplateSectionModal = false;

    public ?string $templateSectionParentKey = null;

    public ?string $templateSectionParentLabel = null;

    public array $newTemplateSectionData = [
        'label' => '',
    ];

    public function getHeading(): string
    {
        return 'Master Template';
    }

    public function getSubheading(): ?string
    {
        return 'Create, manage, preview, and publish the platform Master Template from one workspace.';
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Master Data',
            'Master Template',
        ];
    }

    public function mount(): void
    {
        parent::mount();

        $hasMasterQuestions = VerificationFormQuestion::query()
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->exists();

        if (! $hasMasterQuestions) {
            VerificationTemplateThreeDefaults::syncMasterQuestions();
        }

        $requestedVersionId = request()->integer('version');

        if ($requestedVersionId > 0) {
            $this->selectTemplateVersion($requestedVersionId);
        }
    }

    protected function getHeaderActions(): array
    {
        return $this->getTemplateHeaderActions();
    }

    protected function getTemplateHeaderActions(): array
    {
        if (! $this->canManageVersions()) {
            return [];
        }

        $actions = [
            Action::make('createDraftVersion')
                ->label('Create Draft Template')
                ->icon('heroicon-o-document-duplicate')
                ->color('primary')
                ->modalHeading('Create template draft')
                ->modalDescription('Name this draft, choose the form type, and decide whether to start fresh or replicate an existing version.')
                ->form([
                    TextInput::make('template_name')
                        ->label('Template name')
                        ->default('Master Template Draft')
                        ->required()
                        ->maxLength(255),
                    Select::make('form_type')
                        ->label('Type of form')
                        ->options([
                            'both' => 'Full + Short',
                            'full_form' => 'Full Form',
                            'short_form' => 'Short Form',
                        ])
                        ->default('both')
                        ->required()
                        ->native(false),
                    Select::make('starting_point')
                        ->label('Starting point')
                        ->options([
                            'current_master' => 'Start from current Master Template',
                            'fresh' => 'Start with standard sections and no copied questions',
                            'specific_version' => 'Replicate from a specific version',
                        ])
                        ->default('current_master')
                        ->required()
                        ->live()
                        ->native(false),
                    Select::make('source_version_id')
                        ->label('Template version')
                        ->options(fn (): array => $this->draftSourceVersionOptions())
                        ->visible(fn (Get $get): bool => $get('starting_point') === 'specific_version')
                        ->required(fn (Get $get): bool => $get('starting_point') === 'specific_version')
                        ->searchable()
                        ->native(false),
                ])
                ->action(fn (array $data): null => $this->createDraftVersion($data)),
        ];

        if ($this->selectedTemplateVersion()?->status === VerificationTemplateVersion::STATUS_DRAFT) {
            $actions[] = Action::make('publishDraftVersion')
                ->label('Publish This Draft')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Publish this template draft?')
                ->modalDescription('Choose whether this published Master Template is released to clinics or retained for internal SaaS use. Existing verification requests keep their original snapshot until refreshed.')
                ->form([
                    TextInput::make('version_name')
                        ->label('Version name')
                        ->default(fn (): string => $this->selectedTemplateVersion()?->name ?: 'Master Template Draft')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('change_description')
                        ->label('Change description')
                        ->placeholder('Describe what changed in this version.')
                        ->required()
                        ->rows(4)
                        ->maxLength(2000),
                    Select::make('release_mode')
                        ->label('Clinic release')
                        ->options([
                            'release_to_clinics' => 'Publish & Release to Clinics',
                            'internal_only' => 'Publish Internally',
                        ])
                        ->default('release_to_clinics')
                        ->helperText('Released templates can be used for new clinic template copies. Internal templates remain available only in SaaS.')
                        ->required()
                        ->native(false),
                ])
                ->action(fn (array $data): null => $this->publishDraftVersion($data));
        }

        if ($this->selectedTemplateVersion()?->canEditDirectly()) {
            $actions[] = Action::make('editDraftDetails')
                ->label('Edit Draft Details')
                ->icon('heroicon-o-pencil-square')
                ->modalHeading('Edit template draft')
                ->modalDescription('Unused drafts can be renamed and reconfigured directly. This option locks automatically once the template is published, copied, or used by a request.')
                ->form([
                    TextInput::make('name')
                        ->label('Template name')
                        ->default(fn (): string => $this->selectedTemplateVersion()?->name ?: 'Master Template Draft')
                        ->required()
                        ->maxLength(255),
                    Select::make('form_type')
                        ->label('Type of form')
                        ->options(VerificationTemplateVersion::FORM_TYPE_OPTIONS)
                        ->default(fn (): string => $this->selectedTemplateVersion()?->form_type ?: VerificationTemplateVersion::FORM_TYPE_BOTH)
                        ->required()
                        ->native(false),
                    Textarea::make('notes')
                        ->label('Internal notes')
                        ->default(fn (): ?string => $this->selectedTemplateVersion()?->notes)
                        ->rows(3)
                        ->maxLength(2000),
                ])
                ->action(fn (array $data): null => $this->updateDraftDetails($data));

            $actions[] = Action::make('deleteUnusedDraft')
                ->label('Delete Unused Draft')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Permanently delete this unused draft?')
                ->modalDescription('The template, its sections, and its questions will be permanently removed. This action is available only because the draft has never been published, copied, or used by a verification request.')
                ->action(fn (): null => $this->deleteSelectedUnusedDraft());
        }

        return $actions;
    }

    public function getVisibleHeaderActions(): array
    {
        return $this->getTemplateHeaderActions();
    }

    public function canManageTemplateVersions(): bool
    {
        return $this->canManageVersions();
    }

    public function getCreateQuestionUrl(?string $sectionKey = null): string
    {
        $version = $this->selectedTemplateVersion();

        if (! $version || $version->status !== VerificationTemplateVersion::STATUS_DRAFT) {
            return VerificationFormQuestionResource::getUrl();
        }

        $parameters = ['version' => $version->getKey()];

        if (filled($sectionKey)) {
            $parameters['section'] = $sectionKey;
        }

        return VerificationFormQuestionResource::getUrl('create', $parameters);
    }

    public function openTemplateSectionModal(?string $parentSectionKey = null): void
    {
        if (! $this->canAddQuestionToSelectedVersion()) {
            Notification::make()
                ->title('Open a draft first')
                ->body('Sections can only be added to an open draft template.')
                ->warning()
                ->send();

            return;
        }

        $parentSection = null;

        if (filled($parentSectionKey)) {
            $parentSection = $this->findSelectedSection($parentSectionKey);

            if (! $parentSection) {
                Notification::make()->title('Parent section not found')->danger()->send();

                return;
            }
        }

        $this->templateSectionParentKey = $parentSectionKey;
        $this->templateSectionParentLabel = $parentSection['title'] ?? null;
        $this->newTemplateSectionData = ['label' => ''];
        $this->showTemplateSectionModal = true;
    }

    public function closeTemplateSectionModal(): void
    {
        $this->showTemplateSectionModal = false;
        $this->templateSectionParentKey = null;
        $this->templateSectionParentLabel = null;
        $this->newTemplateSectionData = ['label' => ''];
    }

    public function createSelectedTemplateSection(): void
    {
        $version = $this->selectedTemplateVersion();

        if (! $version || ! $this->canAddQuestionToSelectedVersion()) {
            Notification::make()
                ->title('Open a draft first')
                ->body('Sections can only be added to an open draft template.')
                ->warning()
                ->send();

            return;
        }

        $data = $this->validate([
            'newTemplateSectionData.label' => ['required', 'string', 'max:255'],
        ])['newTemplateSectionData'];

        $label = trim((string) $data['label']);
        $parentSectionKey = $this->templateSectionParentKey;
        $sectionKey = VerificationTemplateSection::makeSectionKey($label, $parentSectionKey);
        $baseKey = $sectionKey;
        $counter = 2;

        while (VerificationTemplateSection::query()
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->where('template_version_id', $version->getKey())
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('section_key', $sectionKey)
            ->exists()) {
            $sectionKey = $baseKey.'_'.$counter++;
        }

        $sortOrder = ((int) VerificationTemplateSection::query()
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->where('template_version_id', $version->getKey())
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->when(
                filled($parentSectionKey),
                fn (Builder $query) => $query->where('parent_section_key', $parentSectionKey),
                fn (Builder $query) => $query->whereNull('parent_section_key'),
            )
            ->max('sort_order')) + 10;

        VerificationTemplateSection::query()->create([
            'organization_id' => null,
            'clinic_id' => null,
            'template_version_id' => $version->getKey(),
            'template_key' => VerificationFormQuestion::defaultTemplateKey(),
            'section_key' => $sectionKey,
            'parent_section_key' => $parentSectionKey,
            'label' => $label,
            'sort_order' => $sortOrder,
            'is_builtin' => false,
            'is_locked_by_admin' => false,
            'is_active' => true,
        ]);

        if (filled($parentSectionKey) && ! in_array($parentSectionKey, $this->expandedTemplateSectionKeys, true)) {
            $this->expandedTemplateSectionKeys[] = $parentSectionKey;
        }

        $sectionType = filled($parentSectionKey) ? 'Sub-section' : 'Section';
        $parentLabel = $this->templateSectionParentLabel;

        $this->closeTemplateSectionModal();

        Notification::make()
            ->title($sectionType.' added')
            ->body(filled($parentLabel) ? $label.' was added under '.$parentLabel.'.' : $label.' was added to the draft.')
            ->success()
            ->send();
    }

    public function createDraftVersion(array $data = []): null
    {
        if (! $this->canManageVersions()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return null;
        }

        $startingPoint = $data['starting_point'] ?? 'current_master';
        $formType = $data['form_type'] ?? VerificationTemplateVersion::FORM_TYPE_BOTH;
        $clinicVisibility = VerificationTemplateVersion::CLINIC_VISIBILITY_HIDDEN;

        if (! in_array($startingPoint, ['current_master', 'fresh', 'specific_version'], true)
            || ! array_key_exists($formType, VerificationTemplateVersion::FORM_TYPE_OPTIONS)
            || blank(trim((string) ($data['template_name'] ?? '')))) {
            Notification::make()->title('Invalid draft configuration')->danger()->send();

            return null;
        }
        $source = match ($startingPoint) {
            'fresh' => null,
            'specific_version' => VerificationTemplateVersion::query()
                ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
                ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
                ->whereNull('clinic_id')
                ->whereKey((int) ($data['source_version_id'] ?? 0))
                ->first(),
            default => $this->getActiveMasterVersion(),
        };

        if ($startingPoint === 'specific_version' && ! $source) {
            Notification::make()
                ->title('Select a valid template version')
                ->danger()
                ->send();

            return null;
        }

        $draft = app(VerificationTemplateVersionService::class)->createDraftFromSource($source, [
            'template_key' => VerificationFormQuestion::defaultTemplateKey(),
            'scope' => VerificationTemplateVersion::SCOPE_MASTER,
            'name' => trim((string) $data['template_name']),
            'form_type' => $formType,
            'clinic_visibility' => $clinicVisibility,
            'starting_point' => $startingPoint,
        ]);

        $this->selectTemplateVersion($draft->getKey());

        Notification::make()
            ->title('Draft version ready')
            ->body('You are now editing version '.$draft->version_number.' of the Master Template.')
            ->success()
            ->send();

        return null;
    }

    public function draftSourceVersionOptions(): array
    {
        return VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->orderByDesc('version_number')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(fn (VerificationTemplateVersion $version): array => [
                $version->getKey() => 'v'.$version->version_number.' - '.str($version->status)->headline()->toString().' - '.$version->name,
            ])
            ->all();
    }

    public function publishDraftVersion(array $data = []): null
    {
        if (! $this->canManageVersions()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return null;
        }

        $draft = $this->selectedTemplateVersion();

        if (! $draft || $draft->status !== VerificationTemplateVersion::STATUS_DRAFT) {
            Notification::make()
                ->title('Open the draft you want to publish')
                ->body('Publishing requires an explicitly selected Master Template draft.')
                ->warning()
                ->send();

            return null;
        }

        $releaseMode = $data['release_mode'] ?? null;

        if (! in_array($releaseMode, ['release_to_clinics', 'internal_only'], true)) {
            Notification::make()
                ->title('Choose how this template should be published')
                ->body('Select clinic release or internal-only publication before continuing.')
                ->warning()
                ->send();

            return null;
        }

        $clinicVisibility = $releaseMode === 'release_to_clinics'
            ? VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE
            : VerificationTemplateVersion::CLINIC_VISIBILITY_HIDDEN;

        $published = app(VerificationTemplateVersionService::class)->publishDraft(
            $draft,
            $data['version_name'] ?? null,
            $data['change_description'] ?? null,
            $clinicVisibility,
        );

        Notification::make()
            ->title($published->isAvailableToClinics() ? 'Master Template published and released' : 'Master Template published internally')
            ->body($published->isAvailableToClinics()
                ? 'Version '.$published->version_number.' is available for new clinic template copies.'
                : 'Version '.$published->version_number.' remains available only inside SaaS.')
            ->success()
            ->send();

        $this->selectTemplateVersion($published->getKey(), true);

        return null;
    }

    public function updateDraftDetails(array $data): null
    {
        if (! $this->canManageVersions()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return null;
        }

        $draft = $this->selectedTemplateVersion();

        if (! $draft) {
            Notification::make()->title('Open a template draft first')->warning()->send();

            return null;
        }

        $updated = app(VerificationTemplateVersionService::class)->updateUnusedDraft($draft, $data);

        Notification::make()
            ->title('Draft details updated')
            ->body($updated->name.' remains an unpublished working draft.')
            ->success()
            ->send();

        return null;
    }

    public function deleteSelectedUnusedDraft(): null
    {
        if (! $this->canManageVersions()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return null;
        }

        $draft = $this->selectedTemplateVersion();

        if (! $draft) {
            Notification::make()->title('Open a template draft first')->warning()->send();

            return null;
        }

        app(VerificationTemplateVersionService::class)->deleteUnusedDraft($draft);
        $this->closeTemplateVersionPanel();

        Notification::make()
            ->title('Unused draft deleted')
            ->body('The unpublished template and its draft content were permanently removed.')
            ->success()
            ->send();

        return null;
    }

    public function getVersionSummary(): array
    {
        $active = $this->getActiveMasterVersion();
        $draft = $this->getDraftMasterVersion();
        $working = $draft ?: $active;

        return [
            'active_version' => 'v'.$active->version_number,
            'active_published_at' => optional($active->published_at)->format('M d, Y h:i A') ?: 'Not published',
            'working_version' => 'v'.$working->version_number,
            'working_status' => str($working->status)->headline()->toString(),
            'has_draft' => (bool) $draft,
            'draft_version' => $draft ? 'v'.$draft->version_number : null,
        ];
    }

    public function selectTemplateVersion(int $versionId, bool $showPreview = false): void
    {
        $version = $this->masterVersionQuery()
            ->whereKey($versionId)
            ->first();

        if (! $version) {
            Notification::make()
                ->title('Template version not found')
                ->danger()
                ->send();

            return;
        }

        $this->selectedTemplateVersionId = $version->getKey();
        $this->showTemplatePreview = $showPreview;

        $this->expandedTemplateSectionKeys = collect($this->templateSectionRows($version))
            ->filter(fn (array $section): bool => ($section['child_count'] ?? 0) > 0)
            ->pluck('key')
            ->values()
            ->all();

        $this->dispatch('master-template-version-opened');
    }

    public function setWorkingDraft(int $versionId): void
    {
        if (! $this->canManageVersions()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return;
        }

        $draft = $this->masterVersionQuery()
            ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
            ->whereKey($versionId)
            ->first();

        if (! $draft) {
            Notification::make()->title('Master Template draft not found')->danger()->send();

            return;
        }

        if (! $draft->canEditDirectly()) {
            Notification::make()
                ->title('Draft is protected')
                ->body($draft->lifecycleLockReason() ?? 'Only an unused, unpublished draft can be selected.')
                ->danger()
                ->send();

            return;
        }

        app(VerificationTemplateVersionService::class)->markWorkingDraft($draft);
        $this->selectedTemplateVersionId = $draft->getKey();
        $this->showTemplatePreview = false;

        Notification::make()
            ->title('Working draft updated')
            ->body('New Master Template questions will default to version '.$draft->version_number.'.')
            ->success()
            ->send();

        $this->dispatch('master-template-version-opened');
    }

    public function archiveDraft(int $versionId): void
    {
        if (! $this->canManageVersions()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return;
        }

        $draft = $this->masterVersionQuery()
            ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
            ->whereKey($versionId)
            ->first();

        if (! $draft) {
            Notification::make()->title('Master Template draft not found')->danger()->send();

            return;
        }

        if (! $draft->canEditDirectly()) {
            Notification::make()
                ->title('Draft is protected')
                ->body($draft->lifecycleLockReason() ?? 'Only an unused, unpublished draft can be archived.')
                ->danger()
                ->send();

            return;
        }

        app(VerificationTemplateVersionService::class)->archiveUnusedDraft($draft);

        if ($this->selectedTemplateVersionId === $draft->getKey()) {
            $this->closeTemplateVersionPanel();
        }

        Notification::make()->title('Draft archived')->success()->send();
    }

    public function restoreArchivedDraft(int $versionId): void
    {
        if (! $this->canManageVersions()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return;
        }

        $version = $this->masterVersionQuery()
            ->where('status', VerificationTemplateVersion::STATUS_ARCHIVED)
            ->whereNull('published_at')
            ->whereKey($versionId)
            ->first();

        if (! $version) {
            Notification::make()->title('Archived Master Template not found')->danger()->send();

            return;
        }

        $version->forceFill([
            'status' => VerificationTemplateVersion::STATUS_DRAFT,
            'is_active' => false,
            'is_working_draft' => false,
        ])->save();

        $this->selectTemplateVersion($version->getKey());

        Notification::make()
            ->title('Draft restored')
            ->body('Open it in the builder or set it as the working draft when ready.')
            ->success()
            ->send();
    }

    public function showTemplateVersionPreview(int $versionId, string $formType = 'full_form'): void
    {
        $this->templatePreviewFormType = in_array($formType, ['full_form', 'short_form'], true)
            ? $formType
            : 'full_form';

        $this->selectTemplateVersion($versionId, true);
    }

    public function closeTemplateVersionPanel(): void
    {
        $this->selectedTemplateVersionId = null;
        $this->showTemplatePreview = false;
    }

    public function setTemplatePreviewFormType(string $formType): void
    {
        if (! in_array($formType, ['full_form', 'short_form'], true)) {
            return;
        }

        $this->templatePreviewFormType = $formType;
        $this->showTemplatePreview = true;
    }

    public function showTemplateVersionStructure(): void
    {
        $this->showTemplatePreview = false;
    }

    public function toggleTemplateSection(string $sectionKey): void
    {
        if (in_array($sectionKey, $this->expandedTemplateSectionKeys, true)) {
            $this->expandedTemplateSectionKeys = array_values(array_diff($this->expandedTemplateSectionKeys, [$sectionKey]));

            return;
        }

        $this->expandedTemplateSectionKeys[] = $sectionKey;
    }

    public function openSectionQuestionModal(string $sectionKey): void
    {
        $version = $this->selectedTemplateVersion();

        if (! $version || ! $this->canAddQuestionToSelectedVersion()) {
            Notification::make()
                ->title('Open a draft first')
                ->body('Questions can only be added to an open draft template.')
                ->warning()
                ->send();

            return;
        }

        $section = $this->findSelectedSection($sectionKey);

        if (! $section) {
            Notification::make()->title('Section not found')->danger()->send();

            return;
        }

        $this->questionSectionKey = $sectionKey;
        $this->questionSectionLabel = $section['title'];
        $this->newQuestionData = [
            'prompt' => '',
            'input_type' => VerificationFormQuestion::isFrequencyPercentageSection($sectionKey) ? 'frequency_row' : 'text',
            'form_type' => 'both',
            'placeholder' => '',
            'help_text' => '',
            'select_options' => '',
        ];
        $this->showSectionQuestionModal = true;
    }

    public function closeSectionQuestionModal(): void
    {
        $this->showSectionQuestionModal = false;
        $this->questionSectionKey = null;
        $this->questionSectionLabel = null;
    }

    public function createSectionQuestion(): void
    {
        $version = $this->selectedTemplateVersion();

        if (! $version || ! $this->canAddQuestionToSelectedVersion() || blank($this->questionSectionKey)) {
            Notification::make()
                ->title('Open a draft first')
                ->body('Questions can only be added to an open draft template.')
                ->warning()
                ->send();

            return;
        }

        $data = $this->validate([
            'newQuestionData.prompt' => ['required', 'string', 'max:255'],
            'newQuestionData.input_type' => ['required', 'string'],
            'newQuestionData.form_type' => ['required', 'string'],
            'newQuestionData.placeholder' => ['nullable', 'string', 'max:255'],
            'newQuestionData.help_text' => ['nullable', 'string', 'max:1000'],
            'newQuestionData.select_options' => ['nullable', 'string', 'max:2000'],
        ])['newQuestionData'];

        if (! array_key_exists($data['input_type'], VerificationFormQuestion::INPUT_TYPE_OPTIONS)) {
            Notification::make()->title('Invalid answer type')->danger()->send();

            return;
        }

        if (! array_key_exists($data['form_type'], VerificationFormQuestion::FORM_TYPE_OPTIONS)) {
            Notification::make()->title('Invalid form type')->danger()->send();

            return;
        }

        if (in_array($data['input_type'], ['select', 'multi_select'], true) && blank($data['select_options'])) {
            Notification::make()
                ->title('Dropdown options required')
                ->body('Add one option per line before saving this question.')
                ->danger()
                ->send();

            return;
        }

        $sortOrder = ((int) VerificationFormQuestion::query()
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->where('template_version_id', $version->getKey())
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('section_key', $this->questionSectionKey)
            ->max('sort_order')) + 10;

        VerificationFormQuestion::query()->create([
            'organization_id' => null,
            'clinic_id' => null,
            'template_version_id' => $version->getKey(),
            'template_key' => VerificationFormQuestion::defaultTemplateKey(),
            'question_kind' => VerificationFormQuestion::QUESTION_KIND_NORMAL,
            'prompt' => trim((string) $data['prompt']),
            'section_key' => $this->questionSectionKey,
            'form_type' => $data['form_type'],
            'input_type' => $data['input_type'],
            'placeholder' => filled($data['placeholder'] ?? null) ? trim((string) $data['placeholder']) : null,
            'help_text' => filled($data['help_text'] ?? null) ? trim((string) $data['help_text']) : null,
            'select_options' => filled($data['select_options'] ?? null) ? trim((string) $data['select_options']) : null,
            'frequency_response_mode' => VerificationFormQuestion::isFrequencyPercentageSection($this->questionSectionKey) ? 'current' : null,
            'frequency_response_fields' => VerificationFormQuestion::isFrequencyPercentageSection($this->questionSectionKey)
                ? VerificationFormQuestion::defaultFrequencyResponseFields('current')
                : null,
            'sort_order' => $sortOrder,
            'is_builtin' => false,
            'is_locked_by_admin' => false,
            'is_required_for_audit' => false,
            'is_active' => true,
        ]);

        $sectionLabel = $this->questionSectionLabel;

        $this->closeSectionQuestionModal();

        Notification::make()
            ->title('Question added')
            ->body('The question was added to '.$sectionLabel.'.')
            ->success()
            ->send();
    }

    public function canAddQuestionToSelectedVersion(): bool
    {
        $version = $this->selectedTemplateVersion();

        return $this->canManageVersions()
            && ($version?->canEditDirectly() ?? false)
            && blank($version->organization_id)
            && blank($version->clinic_id);
    }

    public function canAddSubSectionToSection(string $sectionKey): bool
    {
        return $this->canAddQuestionToSelectedVersion()
            && $sectionKey === 'template_3_frequency_percentage';
    }

    protected function selectedTemplateVersion(): ?VerificationTemplateVersion
    {
        if (! $this->selectedTemplateVersionId) {
            return null;
        }

        return $this->masterVersionQuery()
            ->whereKey($this->selectedTemplateVersionId)
            ->first();
    }

    protected function masterVersionQuery(): Builder
    {
        return VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->whereNull('organization_id')
            ->whereNull('clinic_id');
    }

    protected function findSelectedSection(string $sectionKey): ?array
    {
        $detail = $this->getSelectedTemplateVersionDetail();

        if (! $detail) {
            return null;
        }

        return collect($detail['sections'])
            ->flatMap(fn (array $section): array => [$section, ...($section['children'] ?? [])])
            ->firstWhere('key', $sectionKey);
    }

    public function getSelectedTemplateVersionDetail(): ?array
    {
        if (! $this->selectedTemplateVersionId) {
            return null;
        }

        $version = $this->masterVersionQuery()
            ->with(['parentVersion', 'sourceVersion', 'createdBy'])
            ->whereKey($this->selectedTemplateVersionId)
            ->first();

        if (! $version) {
            return null;
        }

        $sections = $this->templateSectionRows($version);
        $questions = $version->questions()
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return [
            'id' => $version->getKey(),
            'name' => $version->name,
            'version' => 'v'.$version->version_number,
            'status' => str($version->status)->headline()->toString(),
            'raw_status' => $version->status,
            'scope' => filled($version->clinic_id) ? 'Clinic' : 'Master',
            'form_type' => VerificationTemplateVersion::FORM_TYPE_OPTIONS[$version->form_type] ?? 'Full + Short',
            'clinic_visibility' => $version->clinicAvailabilityLabel(),
            'is_active' => (bool) $version->is_active,
            'is_working_draft' => (bool) $version->is_working_draft,
            'is_draft' => $version->status === VerificationTemplateVersion::STATUS_DRAFT,
            'can_add_questions' => $this->canAddQuestionToSelectedVersion(),
            'can_edit_directly' => $this->canManageVersions() && $version->canEditDirectly(),
            'can_delete_permanently' => $this->canManageVersions() && $version->canDeletePermanently(),
            'lock_reason' => $version->lifecycleLockReason(),
            'published_at' => optional($version->published_at)->format('M d, Y h:i A') ?: '-',
            'created_at' => optional($version->created_at)->format('M d, Y h:i A') ?: '-',
            'created_by' => $version->createdBy?->name ?: '-',
            'source_version' => $version->sourceVersion ? 'v'.$version->sourceVersion->version_number : '-',
            'parent_version' => $version->parentVersion ? 'v'.$version->parentVersion->version_number : '-',
            'notes' => $version->notes ?: '-',
            'sections' => $sections,
            'section_count' => count($sections),
            'sub_section_count' => collect($sections)->sum('child_count'),
            'question_count' => $questions->count(),
            'active_question_count' => $questions->where('is_active', true)->count(),
            'inactive_question_count' => $questions->where('is_active', false)->count(),
            'full_question_count' => $questions->whereIn('form_type', ['full_form', 'both'])->count(),
            'short_question_count' => $questions->whereIn('form_type', ['short_form', 'both'])->count(),
            'preview_sections' => $this->templatePreviewSections($version, $this->templatePreviewFormType),
            'question_rows' => $questions->map(fn (VerificationFormQuestion $question): array => [
                'id' => $question->getKey(),
                'prompt' => $question->prompt,
                'section' => VerificationFormQuestion::sectionLabel($question->section_key, $question->template_key),
                'form_type' => VerificationFormQuestion::FORM_TYPE_OPTIONS[$question->form_type] ?? str($question->form_type)->headline()->toString(),
                'answer_type' => VerificationFormQuestion::INPUT_TYPE_OPTIONS[$question->input_type] ?? str($question->input_type)->headline()->toString(),
                'sort_order' => $question->sort_order,
                'is_active' => (bool) $question->is_active,
                'edit_url' => $version->status === VerificationTemplateVersion::STATUS_DRAFT
                    ? VerificationFormQuestionResource::getUrl('edit', [
                        'record' => $question,
                        'version' => $version->getKey(),
                    ])
                    : null,
            ])->all(),
        ];
    }

    public function getTemplateWorkspaceStats(): array
    {
        $working = VerificationFormQuestionResource::currentMasterWorkingVersion();

        $questions = VerificationFormQuestion::query()
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->when($working, fn (Builder $query) => $query->where('template_version_id', $working->getKey()))
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->get();

        $sections = VerificationTemplateSection::query()
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->when($working, fn (Builder $query) => $query->where('template_version_id', $working->getKey()))
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('is_active', true)
            ->get();

        $sectionCount = $sections->count();

        return [
            'sections' => $sectionCount ?: $questions->pluck('section_key')->filter()->unique()->count(),
            'main_sections' => $sectionCount
                ? $sections->whereNull('parent_section_key')->count()
                : $questions->pluck('section_key')->filter()->unique()->count(),
            'sub_sections' => $sectionCount ? $sections->whereNotNull('parent_section_key')->count() : 0,
            'questions' => $questions->count(),
            'active_questions' => $questions->where('is_active', true)->count(),
            'inactive_questions' => $questions->where('is_active', false)->count(),
            'system_questions' => $questions->where('is_builtin', true)->count(),
            'full_questions' => $questions->whereIn('form_type', ['full_form', 'both'])->count(),
            'short_questions' => $questions->whereIn('form_type', ['short_form', 'both'])->count(),
        ];
    }

    public function getDraftContentSummary(): ?array
    {
        $draft = $this->getDraftMasterVersion();

        if (! $draft) {
            return null;
        }

        $questions = $draft->questions()
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->get();

        $sectionTree = $this->templateSectionRows($draft);

        return [
            'version' => 'v'.$draft->version_number,
            'source_version' => $draft->parentVersion ? 'v'.$draft->parentVersion->version_number : null,
            'created_at' => optional($draft->created_at)->format('M d, Y h:i A'),
            'section_count' => count($sectionTree),
            'sub_section_count' => collect($sectionTree)->sum('child_count'),
            'question_count' => $questions->count(),
            'active_question_count' => $questions->where('is_active', true)->count(),
            'inactive_question_count' => $questions->where('is_active', false)->count(),
            'sections' => $sectionTree,
            'is_empty' => $questions->isEmpty(),
        ];
    }

    public function getDraftReviewSummary(): ?array
    {
        $draft = $this->getDraftMasterVersion();

        if (! $draft) {
            return null;
        }

        $published = $this->getActiveMasterVersion();
        $publishedSnapshot = $this->versionReviewSnapshot($published);
        $draftSnapshot = $this->versionReviewSnapshot($draft);
        $sectionKeys = collect(array_keys($publishedSnapshot['sections']))
            ->merge(array_keys($draftSnapshot['sections']))
            ->unique()
            ->values();

        $sectionChanges = $sectionKeys
            ->map(function (string $sectionKey) use ($publishedSnapshot, $draftSnapshot): array {
                $publishedSection = $publishedSnapshot['sections'][$sectionKey] ?? null;
                $draftSection = $draftSnapshot['sections'][$sectionKey] ?? null;

                $status = match (true) {
                    ! $publishedSection && $draftSection => 'added',
                    $publishedSection && ! $draftSection => 'removed',
                    $publishedSection && $draftSection && (
                        $publishedSection['title'] !== $draftSection['title']
                        || $publishedSection['active_count'] !== $draftSection['active_count']
                        || $publishedSection['total_count'] !== $draftSection['total_count']
                        || $publishedSection['sort_order'] !== $draftSection['sort_order']
                    ) => 'changed',
                    default => 'unchanged',
                };

                return [
                    'key' => $sectionKey,
                    'parent' => $publishedSection['parent'] ?? $draftSection['parent'] ?? null,
                    'sort_order' => $draftSection['sort_order'] ?? $publishedSection['sort_order'] ?? 0,
                    'published_title' => $publishedSection['title'] ?? '-',
                    'draft_title' => $draftSection['title'] ?? '-',
                    'published_count' => $publishedSection ? $publishedSection['active_count'].'/'.$publishedSection['total_count'] : '-',
                    'draft_count' => $draftSection ? $draftSection['active_count'].'/'.$draftSection['total_count'] : '-',
                    'status' => $status,
                ];
            })
            ->sortBy('sort_order')
            ->values();

        $questionKeys = collect(array_keys($publishedSnapshot['questions']))
            ->merge(array_keys($draftSnapshot['questions']))
            ->unique()
            ->values();

        $questionChanges = $questionKeys
            ->map(function (string $questionKey) use ($publishedSnapshot, $draftSnapshot): array {
                $publishedQuestion = $publishedSnapshot['questions'][$questionKey] ?? null;
                $draftQuestion = $draftSnapshot['questions'][$questionKey] ?? null;

                $status = match (true) {
                    ! $publishedQuestion && $draftQuestion => 'added',
                    $publishedQuestion && ! $draftQuestion => 'removed',
                    $publishedQuestion && $draftQuestion && (
                        $publishedQuestion['prompt'] !== $draftQuestion['prompt']
                        || $publishedQuestion['section_key'] !== $draftQuestion['section_key']
                        || $publishedQuestion['form_type'] !== $draftQuestion['form_type']
                        || $publishedQuestion['input_type'] !== $draftQuestion['input_type']
                        || $publishedQuestion['is_active'] !== $draftQuestion['is_active']
                        || $publishedQuestion['sort_order'] !== $draftQuestion['sort_order']
                    ) => 'changed',
                    default => 'unchanged',
                };

                return [
                    'published_prompt' => $publishedQuestion['prompt'] ?? '-',
                    'draft_prompt' => $draftQuestion['prompt'] ?? '-',
                    'published_section' => $publishedQuestion['section_title'] ?? '-',
                    'draft_section' => $draftQuestion['section_title'] ?? '-',
                    'status' => $status,
                ];
            })
            ->filter(fn (array $change): bool => $change['status'] !== 'unchanged')
            ->values();

        return [
            'published' => [
                'version' => 'v'.$published->version_number,
                'sections' => count($publishedSnapshot['sections']),
                'questions' => count($publishedSnapshot['questions']),
                'active_questions' => collect($publishedSnapshot['questions'])->where('is_active', true)->count(),
            ],
            'draft' => [
                'version' => 'v'.$draft->version_number,
                'sections' => count($draftSnapshot['sections']),
                'questions' => count($draftSnapshot['questions']),
                'active_questions' => collect($draftSnapshot['questions'])->where('is_active', true)->count(),
            ],
            'totals' => [
                'sections_added' => $sectionChanges->where('status', 'added')->count(),
                'sections_removed' => $sectionChanges->where('status', 'removed')->count(),
                'sections_changed' => $sectionChanges->where('status', 'changed')->count(),
                'questions_added' => $questionChanges->where('status', 'added')->count(),
                'questions_removed' => $questionChanges->where('status', 'removed')->count(),
                'questions_changed' => $questionChanges->where('status', 'changed')->count(),
            ],
            'section_changes' => $sectionChanges->all(),
            'section_tree_changes' => $this->sectionChangeTree($sectionChanges),
            'question_changes' => $questionChanges->take(30)->all(),
            'has_question_changes' => $questionChanges->isNotEmpty(),
        ];
    }

    protected function sectionChangeTree($sectionChanges): array
    {
        return $sectionChanges
            ->whereNull('parent')
            ->map(function (array $change) use ($sectionChanges): array {
                return [
                    ...$change,
                    'children' => $sectionChanges
                        ->where('parent', $change['key'])
                        ->sortBy('sort_order')
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function versionReviewSnapshot(VerificationTemplateVersion $version): array
    {
        $questions = $version->questions()
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sections = $version->sections()
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->keyBy('section_key');

        $questionsBySection = $questions->groupBy(fn (VerificationFormQuestion $question): string => $question->section_key ?: 'unassigned');
        $sectionKeys = $sections->keys()
            ->merge($questionsBySection->keys())
            ->filter()
            ->unique()
            ->values();

        $sectionRows = $sectionKeys
            ->mapWithKeys(function (string $sectionKey) use ($sections, $questionsBySection): array {
                $section = $sections->get($sectionKey);
                $sectionQuestions = $questionsBySection->get($sectionKey, collect());

                return [$sectionKey => [
                    'title' => $section?->label ?: VerificationFormQuestion::sectionLabel(
                        $sectionKey,
                        VerificationFormQuestion::defaultTemplateKey(),
                    ),
                    'parent' => $section?->parent_section_key,
                    'sort_order' => $section?->sort_order ?? $sectionQuestions->min('sort_order') ?? 0,
                    'active_count' => $sectionQuestions->where('is_active', true)->count(),
                    'total_count' => $sectionQuestions->count(),
                ]];
            })
            ->all();

        $questionRows = $questions
            ->mapWithKeys(function (VerificationFormQuestion $question) use ($sectionRows, $version): array {
                $sectionKey = $question->section_key ?: 'unassigned';
                $reviewKey = filled($question->source_question_id)
                    ? 'source:'.$question->source_question_id
                    : ($version->status === VerificationTemplateVersion::STATUS_DRAFT ? 'draft:'.$question->id : 'source:'.$question->id);

                return [$reviewKey => [
                    'prompt' => filled($question->code) ? "{$question->code} {$question->prompt}" : $question->prompt,
                    'section_key' => $sectionKey,
                    'section_title' => $sectionRows[$sectionKey]['title'] ?? VerificationFormQuestion::sectionLabel(
                        $sectionKey,
                        VerificationFormQuestion::defaultTemplateKey(),
                    ),
                    'form_type' => $question->form_type,
                    'input_type' => $question->input_type,
                    'is_active' => (bool) $question->is_active,
                    'sort_order' => (int) $question->sort_order,
                ]];
            })
            ->all();

        return [
            'sections' => $sectionRows,
            'questions' => $questionRows,
        ];
    }

    public function getActiveMasterVersion(): VerificationTemplateVersion
    {
        return app(VerificationTemplateVersionService::class)->ensureMasterVersion(
            VerificationFormQuestion::defaultTemplateKey(),
        );
    }

    public function getDraftMasterVersion(): ?VerificationTemplateVersion
    {
        return VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->orderByDesc('is_working_draft')
            ->latest('id')
            ->first();
    }

    protected function canManageVersions(): bool
    {
        return auth()->user()?->canManageVerificationTemplateSections() ?? false;
    }

    public function getBuiltInSections(): array
    {
        return $this->getTemplateSectionOverview();
    }

    public function getTemplateSectionOverview(): array
    {
        return $this->getTemplateSectionTree();
    }

    public function getTemplateSectionTree(): array
    {
        return $this->templateSectionRows(VerificationFormQuestionResource::currentMasterWorkingVersion());
    }

    protected function templateSectionRows(?VerificationTemplateVersion $version): array
    {
        $questions = VerificationFormQuestion::query()
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->when($version, fn (Builder $query) => $query->where('template_version_id', $version->getKey()))
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (VerificationFormQuestion $question): string => $question->section_key ?: 'unassigned');

        $sections = VerificationTemplateSection::query()
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->when($version, fn (Builder $query) => $query->where('template_version_id', $version->getKey()))
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sectionKeys = $sections->pluck('section_key')
            ->merge($questions->keys())
            ->filter()
            ->unique()
            ->values();

        $rows = $sectionKeys
            ->map(fn (string $sectionKey): array => $this->templateSectionRow(
                $sectionKey,
                $sections->firstWhere('section_key', $sectionKey),
                $questions->get($sectionKey, collect()),
            ))
            ->sortBy('sort_order')
            ->values();

        return $rows
            ->whereNull('parent')
            ->map(function (array $row) use ($rows): array {
                $children = $rows
                    ->where('parent', $row['key'])
                    ->sortBy('sort_order')
                    ->values()
                    ->all();

                return [
                    ...$row,
                    'children' => $children,
                    'child_count' => count($children),
                    'tree_active_count' => $row['active_count'] + collect($children)->sum('active_count'),
                    'tree_count' => $row['count'] + collect($children)->sum('count'),
                ];
            })
            ->values()
            ->all();
    }

    protected function templateSectionRow(string $sectionKey, ?VerificationTemplateSection $section, $sectionQuestions): array
    {
        $activeQuestions = $sectionQuestions->where('is_active', true);
        $activeCount = $activeQuestions->count();
        $systemCount = $sectionQuestions->where('is_builtin', true)->count();

        return [
            'key' => $sectionKey,
            'title' => $section?->label ?: VerificationFormQuestion::sectionLabel(
                $sectionKey,
                VerificationFormQuestion::defaultTemplateKey(),
            ),
            'parent' => $section?->parent_section_key,
            'sort_order' => $section?->sort_order ?? $sectionQuestions->min('sort_order') ?? 0,
            'count' => $sectionQuestions->count(),
            'active_count' => $activeCount,
            'inactive_count' => $sectionQuestions->where('is_active', false)->count(),
            'system_count' => $systemCount,
            'full_count' => $sectionQuestions->whereIn('form_type', ['full_form', 'both'])->count(),
            'short_count' => $sectionQuestions->whereIn('form_type', ['short_form', 'both'])->count(),
            'questions' => $activeQuestions->take(3)->map(function (VerificationFormQuestion $question): array {
                return [
                    'prompt' => filled($question->code) ? "{$question->code} {$question->prompt}" : $question->prompt,
                    'is_builtin' => $question->is_builtin,
                    'form_type' => VerificationFormQuestion::FORM_TYPE_OPTIONS[$question->form_type] ?? str($question->form_type)->headline()->toString(),
                ];
            })->all(),
            'children' => [],
            'child_count' => 0,
            'tree_active_count' => $activeCount,
            'tree_count' => $sectionQuestions->count(),
        ];
    }

    public function getSelectedClinicId(): ?int
    {
        $candidate = data_get($this->tableFilters, 'clinic_id.value')
            ?? data_get($this->tableFilters, 'clinic_id')
            ?? null;

        return filled($candidate) ? (int) $candidate : null;
    }

    public function getSelectedClinicName(): ?string
    {
        $clinicId = $this->getSelectedClinicId();

        if (! $clinicId) {
            return null;
        }

        return Clinic::query()->whereKey($clinicId)->value('clinic_name');
    }

    public function getTemplateVersionHistory(): array
    {
        return $this->masterVersionQuery()
            ->with('clinic.organization')
            ->orderByDesc('version_number')
            ->orderByDesc('id')
            ->get()
            ->map(function (VerificationTemplateVersion $version): array {
                $isDraft = $version->status === VerificationTemplateVersion::STATUS_DRAFT;
                $isAvailableToClinics = $version->isAvailableToClinics();

                return [
                    'id' => $version->getKey(),
                    'name' => $version->name,
                    'version' => 'v'.$version->version_number,
                    'row_group' => $isDraft
                        ? 'draft'
                        : ($version->status === VerificationTemplateVersion::STATUS_ARCHIVED
                            ? 'archived'
                            : ((bool) $version->is_active ? 'active' : 'previous')),
                    'description' => $isDraft
                        ? ($version->is_working_draft
                            ? 'Current working draft for new Master Template questions.'
                            : 'Editable draft. Open the builder or set it as the working draft.')
                        : ((bool) $version->is_active ? 'Current active Master Template' : 'Previous Master Template version'),
                    'scope' => filled($version->clinic_id) ? 'Clinic' : 'Master',
                    'clinic' => $version->clinic?->clinic_name,
                    'form_type' => VerificationTemplateVersion::FORM_TYPE_OPTIONS[$version->form_type] ?? 'Full + Short',
                    'clinic_visibility' => $version->clinicAvailabilityLabel(),
                    'status' => str($version->status)->headline()->toString(),
                    'status_label' => $isDraft
                        ? ($version->is_working_draft ? 'Working Draft' : 'Draft')
                        : ($version->status === VerificationTemplateVersion::STATUS_ARCHIVED
                            ? 'Archived'
                            : ((bool) $version->is_active
                                ? ($isAvailableToClinics ? 'Published & Available' : 'Published - Internal Only')
                                : 'Previous Published')),
                    'is_available_to_clinics' => $isAvailableToClinics,
                    'is_active' => (bool) $version->is_active,
                    'is_working_draft' => (bool) $version->is_working_draft,
                    'is_draft' => $isDraft,
                    'can_edit_directly' => $this->canManageVersions() && $version->canEditDirectly(),
                    'can_delete_permanently' => $this->canManageVersions() && $version->canDeletePermanently(),
                    'lock_reason' => $version->lifecycleLockReason(),
                    'can_restore' => $version->status === VerificationTemplateVersion::STATUS_ARCHIVED
                        && blank($version->published_at),
                    'updated_at' => optional($version->updated_at)->format('M d, Y h:i A') ?: '-',
                    'published_at' => optional($version->published_at)->format('M d, Y h:i A'),
                    'notes' => $version->notes,
                ];
            })
            ->all();
    }

    protected function templatePreviewSections(VerificationTemplateVersion $version, string $formType): array
    {
        $allowedFormTypes = $formType === 'short_form'
            ? ['short_form', 'both']
            : ['full_form', 'both'];

        $questions = $version->questions()
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('is_active', true)
            ->whereIn('form_type', $allowedFormTypes)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (VerificationFormQuestion $question): string => $question->section_key ?: 'unassigned');

        $sections = VerificationTemplateSection::query()
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->where('template_version_id', $version->getKey())
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->keyBy('section_key');

        return $questions
            ->map(function ($sectionQuestions, string $sectionKey) use ($sections): array {
                return [
                    'key' => $sectionKey,
                    'title' => $sections->get($sectionKey)?->label ?: VerificationFormQuestion::sectionLabel(
                        $sectionKey,
                        VerificationFormQuestion::defaultTemplateKey(),
                    ),
                    'questions' => $sectionQuestions->map(fn (VerificationFormQuestion $question): array => [
                        'prompt' => filled($question->code) ? "{$question->code} {$question->prompt}" : $question->prompt,
                        'input_type' => VerificationFormQuestion::INPUT_TYPE_OPTIONS[$question->input_type] ?? str($question->input_type)->headline()->toString(),
                        'help_text' => $question->help_text,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
