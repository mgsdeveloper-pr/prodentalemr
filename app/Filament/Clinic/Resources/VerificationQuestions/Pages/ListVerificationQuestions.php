<?php

namespace App\Filament\Clinic\Resources\VerificationQuestions\Pages;

use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateSection;
use App\Models\VerificationTemplateVersion;
use App\Support\ClinicPanelScope;
use App\Support\VerificationTemplateVersionService;
use App\Support\VerificationTemplateImport;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListVerificationQuestions extends Page
{
    use \Livewire\WithFileUploads;
    use Concerns\InteractsWithInlineTemplateBuilder;

    public $upload;
    public bool $showSectionUpload = false;
    public bool $confirmSectionUpload = false;
    #[\Livewire\Attributes\Locked]
    public array $sectionUploadRows = [];
    #[\Livewire\Attributes\Locked]
    public array $sectionUploadErrors = [];
    #[\Livewire\Attributes\Locked]
    public ?int $uploadVersionId = null;
    #[\Livewire\Attributes\Locked]
    public ?string $uploadSectionKey = null;
    #[\Livewire\Attributes\Locked]
    public string $uploadToken = '';
    protected static string $resource = VerificationQuestionResource::class;

    protected string $view = 'filament.clinic.resources.verification-questions.pages.list-verification-questions';

    public string $selectedTemplateKey = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY;

    public bool $showDraft = false;

    public ?int $selectedTemplateVersionId = null;

    public bool $showCreateDraftModal = false;
    public bool $showPublishReview = false;
    #[\Livewire\Attributes\Locked] public array $publishReview = [];
    public bool $structuredLayout = false;
    public string $draftFormType = 'full_form';
    public string $builderFormType = 'full_form';
    public string $draftName = '';
    public array $data = [];
    public array $codeCoverageData = [];

    public bool $showTemplateSectionModal = false;

    public ?string $selectedSectionKey = null;

    public string $questionSearch = '';

    public string $questionStatus = 'all';

    public string $questionOwnership = 'all';

    public string $builderView = 'questions';

    public string $pendingBuilderAction = 'draft';

    public string $templateSectionMode = 'section';

    protected ?Collection $questionSectionsCache = null;

    protected ?Collection $templateBuilderSectionsCache = null;

    protected bool $activeVersionResolved = false;

    protected ?VerificationTemplateVersion $activeVersionCache = null;

    protected bool $draftVersionResolved = false;

    protected ?VerificationTemplateVersion $draftVersionCache = null;

    protected bool $displayedVersionResolved = false;

    protected ?VerificationTemplateVersion $displayedVersionCache = null;

    public array $newTemplateSectionData = [
        'label' => '',
        'parent_section_key' => null,
    ];

    protected $queryString = [
        'selectedTemplateKey' => ['except' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY, 'as' => 'template'],
        'showDraft' => ['except' => false, 'as' => 'draft'],
        'selectedTemplateVersionId' => ['except' => null, 'as' => 'version'],
        'selectedSectionKey' => ['except' => null, 'as' => 'section'],
        'builderFormType' => ['except' => 'full_form', 'as' => 'form'],
    ];

    public function mount(): void
    {
        if (! $this->selectedTemplateVersionId) {
            $this->redirect(\App\Filament\Clinic\Pages\VerificationSettings::getUrl(['section' => 'template-management']), navigate: true);
            return;
        }
        if ($this->selectedTemplateVersionId && ! request()->has('form')) {
            $version = VerificationTemplateVersion::query()->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
                ->where('clinic_id', ClinicPanelScope::selectedClinicId())->find($this->selectedTemplateVersionId);
            if ($version && in_array($version->form_type, ['full_form', 'short_form'], true)) {
                $this->builderFormType = $version->form_type;
            }
        }
        $version = $this->getDisplayedClinicVersion();
        abort_unless($version, 404);
        $this->showDraft = $version->canEditDirectly() && $this->canManageSelectedClinicTemplateSections();
    }

    public function reviewPublishing(): void
    {
        if ($this->guardEditor('reviewPublishing')) return;
        abort_unless($this->isDraftEditingOpen() && $this->canPublishSelectedTemplate(), 403);
        app(VerificationTemplateVersionService::class)->assertPublishable($this->getDisplayedClinicVersion());
        $version = $this->getDisplayedClinicVersion();
        $active = app(VerificationTemplateVersionService::class)->activeClinicForms($version->clinic_id);
        $labels = [];
        foreach ($active as $form => $id) {
            if (! in_array($version->form_type, ['both', $form], true)) continue;
            $old = VerificationTemplateVersion::find($id);
            $labels[$form] = $old ? $old->name.' · v'.$old->version_number : 'None';
        }
        $this->publishReview = ['version_id' => $version->id, 'expected' => $active, 'labels' => $labels];
        $this->showPublishReview = true;
    }

    public function getImportHistory(): Collection
    {
        $version = $this->getDisplayedClinicVersion();
        return $version
            ? \App\Models\VerificationTemplateImportReceipt::query()
                ->where('clinic_id', $version->clinic_id)
                ->where('template_version_id', $version->id)->latest()->get()
            : collect();
    }

    public function getTitle(): string
    {
        return 'Clinic Template';
    }

    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getVisibleHeaderActions(): array
    {
        return [];
    }

    public function openCreateDraftModal(string $action = 'draft'): void
    {
        if (! $this->canManageSelectedClinicTemplateSections()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return;
        }

        $this->pendingBuilderAction = in_array($action, ['questions', 'reorder'], true)
            ? $action
            : 'draft';
        $this->draftFormType = $this->builderFormType;
        $this->draftName = '';
        $this->showCreateDraftModal = true;
    }

    public function closeCreateDraftModal(): void
    {
        $this->showCreateDraftModal = false;
    }

    public function submitCreateDraftVersion(): void
    {
        $this->draftName = trim($this->draftName);
        $this->validate(['draftName' => 'required|string|max:255', 'builderFormType' => 'required|in:full_form,short_form']);
        $published = $this->getDisplayedClinicVersion();
        $clinicName = $this->getSelectedClinicName() ?: 'Clinic';

        if (! $published) {
            Notification::make()->title('No published clinic template found')->danger()->send();

            return;
        }

        $data = [
            'template_name' => trim($this->draftName),
            'form_type' => $this->builderFormType,
            'structured_layout' => $this->structuredLayout,
            'clinic_visibility' => $published->clinic_visibility ?: VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE,
        ];

        $this->createDraftVersion($data);
        $this->resetBuilderCaches();

        if (! $this->getDraftClinicVersion()) {
            return;
        }

        $this->builderView = $this->pendingBuilderAction === 'reorder' ? 'reorder' : 'questions';
        $this->closeCreateDraftModal();

        if ($this->pendingBuilderAction === 'questions') {
            $this->redirectToQuestionCreate();
        }
    }

    public function beginTemplateChange(string $action = 'questions'): void
    {
        if (! $this->canManageSelectedClinicTemplateSections()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return;
        }

        $this->pendingBuilderAction = $action === 'reorder' ? 'reorder' : 'questions';
        $this->selectedSectionKey ??= $this->getSelectedBuilderSection()['key'] ?? null;

        if ($this->getDraftClinicVersion()?->canEditDirectly()) {
            $this->openDraftVersion();
            $this->builderView = $this->pendingBuilderAction;

            if ($this->pendingBuilderAction === 'questions') {
                $this->redirectToQuestionCreate();
            }

            return;
        }

        $this->openCreateDraftModal($this->pendingBuilderAction);
    }

    public function openTemplateSectionModal(string $mode = 'section'): void
    {
        if ($this->guardEditor('openTemplateSectionModal', [$mode])) return;
        if (! $this->isDraftEditingOpen()) {
            Notification::make()
                ->title('Open a draft first')
                ->body('Sections and sub-sections can only be added while editing a draft.')
                ->warning()
                ->send();

            return;
        }

        $this->templateSectionMode = $mode === 'sub_section' ? 'sub_section' : 'section';
        $this->newTemplateSectionData = [
            'label' => '',
            'parent_section_key' => null,
        ];
        $this->showTemplateSectionModal = true;
    }

    public function closeTemplateSectionModal(): void
    {
        $this->showTemplateSectionModal = false;
    }

    public function submitTemplateSection(): void
    {
        $rules = [
            'newTemplateSectionData.label' => ['required', 'string', 'max:255'],
        ];

        if ($this->templateSectionMode === 'sub_section') {
            $rules['newTemplateSectionData.parent_section_key'] = ['required', 'string'];
        }

        $data = $this->validate($rules)['newTemplateSectionData'];

        $this->createTemplateSection([
            'template_key' => $this->selectedTemplateKey,
            'label' => $data['label'],
            'parent_section_key' => $this->templateSectionMode === 'sub_section'
                ? ($data['parent_section_key'] ?? null)
                : null,
        ]);

        $this->closeTemplateSectionModal();
    }

    public function createTemplateSection(array $data): void
    {
        if (! $this->canManageSelectedClinicTemplateSections()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return;
        }

        $clinic = ClinicPanelScope::selectedClinic();

        if (! $clinic) {
            Notification::make()->title('Select a clinic first')->danger()->send();

            return;
        }

        $templateVersionId = $this->getDraftClinicVersion()?->getKey();

        if (! $this->getDraftClinicVersion() || ! $templateVersionId) {
            Notification::make()
                ->title('Create a draft first')
                ->body('Published clinic template versions are read-only. Create a draft before changing sections.')
                ->danger()
                ->send();

            return;
        }

        $sectionKey = VerificationTemplateSection::makeSectionKey((string) $data['label'], $data['parent_section_key'] ?? null);
        $baseKey = $sectionKey;
        $counter = 2;

        while (VerificationTemplateSection::query()
            ->where('clinic_id', $clinic->id)
            ->where('template_version_id', $templateVersionId)
            ->where('template_key', $data['template_key'])
            ->where('section_key', $sectionKey)
            ->exists()) {
            $sectionKey = $baseKey.'_'.$counter++;
        }

        VerificationTemplateSection::query()->create([
            'organization_id' => $clinic->organization_id,
            'clinic_id' => $clinic->id,
            'template_version_id' => $templateVersionId,
            'template_key' => $data['template_key'],
            'section_key' => $sectionKey,
            'parent_section_key' => $data['parent_section_key'] ?? null,
            'label' => $data['label'],
            'sort_order' => ((int) VerificationTemplateSection::query()
                ->where('clinic_id', $clinic->id)
                ->where('template_version_id', $templateVersionId)
                ->where('template_key', $data['template_key'])
                ->max('sort_order')) + 10,
            'is_active' => true,
        ]);

        Notification::make()
            ->title('Template section created')
            ->body('The new section is now available while creating questions.')
            ->success()
            ->send();
    }

    public function getSelectedClinicName(): ?string
    {
        return ClinicPanelScope::selectedClinic()?->clinic_name;
    }

    public function getVersionSummary(): array
    {
        $active = $this->getActiveClinicVersion();
        $draft = $this->getDraftClinicVersion();
        $clinic = ClinicPanelScope::selectedClinic();
        $displayed = $this->getDisplayedClinicVersion();

        return [
            'clinic_name' => $clinic?->clinic_name,
            'displayed_name' => $displayed?->name ?? 'No template selected',
            'displayed_id' => $displayed?->id,
            'displayed_version' => $displayed ? 'v'.$displayed->version_number : '-',
            'displayed_status' => $displayed ? str($displayed->status)->headline()->toString() : 'Unavailable',
            'displayed_form_type' => VerificationTemplateVersion::FORM_TYPE_OPTIONS[$displayed?->form_type] ?? '-',
            'lock_reason' => $displayed?->lifecycleLockReason(),
            'can_open_draft' => $draft?->canEditDirectly() === true,
            'uses_section_layout' => (bool) $displayed?->uses_section_layout,
            'uses_section_layout' => (bool) $displayed?->uses_section_layout,
            'active_version' => $active ? 'v'.$active->version_number : 'None',
            'active_name' => $active ? $this->clinicTemplateDisplayName($active->name, $clinic?->clinic_name) : 'No active clinic template',
            'source_master' => $displayed?->sourceVersion?->scope === 'master' ? $displayed->sourceVersion->name.' v'.$displayed->sourceVersion->version_number : null,
            'active_published_at' => optional($active?->published_at)->format('M d, Y h:i A') ?: 'Not published',
            'active_form_type' => VerificationTemplateVersion::FORM_TYPE_OPTIONS[$active?->form_type] ?? 'Full + Short',
            'active_visibility' => 'Clinic Copy',
            'template_id' => ($displayed = $this->getDisplayedClinicVersion())
                ? $this->templateIdentifier($displayed)
                : '-',
            'working_version' => $draft ? 'v'.$draft->version_number : 'No draft',
            'working_name' => $draft ? $this->clinicTemplateDisplayName($draft->name, $clinic?->clinic_name, true) : 'Clinic Template Draft',
            'working_status' => $draft ? 'Draft' : 'Create a draft to edit',
            'working_form_type' => VerificationTemplateVersion::FORM_TYPE_OPTIONS[$draft?->form_type] ?? 'Full + Short',
            'working_visibility' => $draft ? 'Clinic Draft' : 'Read-only',
            'has_draft' => (bool) $draft,
            'showing_draft' => $this->isDraftEditingOpen(),
            'draft_version' => $draft ? 'v'.$draft->version_number : null,
            'can_manage' => $this->canManageSelectedClinicTemplateSections(),
            'manager_edits_enabled' => $clinic?->allowsVerificationManagerTemplateEdits() ?? false,
        ];
    }

    public function openDraftVersion(): null
    {
        $draft = $this->getDraftClinicVersion();

        if (! $draft?->canEditDirectly()) {
            $this->openCreateDraftModal();

            return null;
        }

        $this->showDraft = true;
        $this->selectedTemplateVersionId = $draft->getKey();
        $this->resetBuilderCaches();

        return null;
    }

    public function closeDraftVersion(): null
    {
        $this->redirect(\App\Filament\Clinic\Pages\VerificationSettings::getUrl(['section' => 'template-management']), navigate: true);

        return null;
    }

    public function createDraftVersion(array $data = []): null
    {
        if (! $this->canManageSelectedClinicTemplateSections()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return null;
        }

        $published = $this->getDisplayedClinicVersion();

        if (! $published) {
            Notification::make()->title('Select a clinic first')->danger()->send();

            return null;
        }

        if (($data['structured_layout'] ?? false) && ! in_array($data['form_type'] ?? '', ['short_form', 'full_form'], true)) {
            $this->addError('draftFormType', 'Choose Short Form or Full Form.');
            return null;
        }
        $draft = app(VerificationTemplateVersionService::class)->createDraftFromSource($published, [
            'scope' => $published->scope, 'organization_id' => $published->organization_id,
            'clinic_id' => $published->clinic_id, 'form_type' => $data['form_type'] ?? $published->form_type,
        ]);
        $draft->forceFill([
            'name' => $data['template_name'] ?? $draft->name,
            'form_type' => $data['form_type'] ?? $draft->form_type,
            'clinic_visibility' => $data['clinic_visibility'] ?? $draft->clinic_visibility,
        ])->save();
        if (($data['structured_layout'] ?? false) && ! $draft->uses_section_layout) {
            app(\App\Support\VerificationTemplateHierarchy::class)->arrangeDraft($draft);
        }
        $this->showDraft = true;
        $this->selectedTemplateVersionId = $draft->getKey();
        $this->resetBuilderCaches();

        Notification::make()
            ->title('Draft version ready')
            ->body('You are now editing version '.$draft->version_number.' for '.($published->clinic?->clinic_name ?? 'this clinic').'.')
            ->success()
            ->send();

        return null;
    }

    public function publishDraftVersion(array $data = [], bool $activate = false): null
    {
        if ($this->guardEditor('reviewPublishing')) return null;
        abort_unless($this->canPublishSelectedTemplate(), 403);
        if (! $this->canManageSelectedClinicTemplateSections()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return null;
        }

        $draft = $this->getDraftClinicVersion();

        if (! $draft) {
            Notification::make()->title('No draft version found')->warning()->send();

            return null;
        }

        abort_unless($this->showPublishReview && ($this->publishReview['version_id'] ?? null) === $draft->id, 403);

        $published = app(VerificationTemplateVersionService::class)->publishDraft(
            $draft,
            $data['version_name'] ?? null,
            $data['change_description'] ?? null,
            activate: $activate,
            expectedActive: $this->publishReview['expected'],
        );
        $this->showDraft = false;
        $this->selectedTemplateVersionId = $published->id;
        $this->showPublishReview = false;
        $this->resetBuilderCaches();

        Notification::make()
            ->title('Clinic template published')
            ->body('Version '.$published->version_number.($activate ? ' is now active for this clinic.' : ' is published. Active forms are unchanged.'))
            ->success()
            ->send();

        return null;
    }

    public function getActiveClinicVersion(): ?VerificationTemplateVersion
    {
        if ($this->activeVersionResolved) {
            return $this->activeVersionCache;
        }

        $this->activeVersionResolved = true;
        $clinic = ClinicPanelScope::selectedClinic();

        return $this->activeVersionCache = $clinic
            ? VerificationTemplateVersion::query()->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
                ->where('clinic_id', $clinic->id)->where('template_key', $this->selectedTemplateKey)
                ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
                ->whereIn('form_type', ['both', $this->validBuilderFormType()])
                ->where('active_'.$this->validBuilderFormType(), true)->latest('id')->first()
            : null;
    }

    public function canPublishSelectedTemplate(): bool
    {
        $draft = $this->getDraftClinicVersion();
        return $draft && (auth()->user()?->canPublishVerificationTemplate($draft->clinic) ?? false);
    }

    public function getDraftClinicVersion(): ?VerificationTemplateVersion
    {
        if ($this->draftVersionResolved) {
            return $this->draftVersionCache;
        }

        $this->draftVersionResolved = true;
        $clinic = ClinicPanelScope::selectedClinic();

        if (! $clinic) {
            return null;
        }

        return $this->draftVersionCache = VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
            ->whereIn('form_type', ['both', $this->validBuilderFormType()])
            ->where('clinic_id', $clinic->getKey())
            ->when(
                filled($this->selectedTemplateVersionId),
                fn ($query) => $query->whereKey($this->selectedTemplateVersionId),
            )
            ->orderByDesc('is_working_draft')
            ->latest('id')
            ->first();
    }

    public function getTemplateVersionHistory(): array
    {
        $clinic = ClinicPanelScope::selectedClinic();

        if (! $clinic) {
            return [];
        }

        return VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('clinic_id', $clinic->getKey())
            ->whereIn('form_type', ['both', $this->validBuilderFormType()])
            ->orderByDesc('version_number')
            ->orderByDesc('id')
            ->get()
            ->map(fn (VerificationTemplateVersion $version): array => [
                'id' => $version->id,
                'name' => $version->name,
                'version' => 'v'.$version->version_number,
                'form_type' => VerificationTemplateVersion::FORM_TYPE_OPTIONS[$version->form_type] ?? 'Full + Short',
                'clinic_visibility' => VerificationTemplateVersion::CLINIC_VISIBILITY_OPTIONS[$version->clinic_visibility] ?? 'Visible to Clinics',
                'status' => str($version->status)->headline()->toString(),
                'is_active' => (bool) $version->is_active,
                'is_working_draft' => (bool) $version->is_working_draft,
                'published_at' => optional($version->published_at)->format('M d, Y h:i A'),
                'notes' => $version->notes,
            ])
            ->all();
    }

    protected function validBuilderFormType(): string
    {
        abort_unless(in_array($this->builderFormType, ['full_form', 'short_form'], true), 422);
        return $this->builderFormType;
    }

    public function openSectionUpload(): void
    {
        if ($this->guardEditor('openSectionUpload')) return;
        abort_unless($this->isDraftEditingOpen(), 403);
        $section = $this->getSelectedBuilderSection();
        abort_unless($section, 422);
        $this->uploadVersionId = $this->getDisplayedClinicVersion()->id;
        $this->uploadSectionKey = $section['key'];
        $this->upload = null;
        $this->updatedUpload();
        $this->showSectionUpload = true;
    }

    public function updatedUpload(): void
    {
        $this->sectionUploadRows = [];
        $this->sectionUploadErrors = [];
        $this->confirmSectionUpload = false;
        $this->uploadToken = (string) \Illuminate\Support\Str::uuid();
        $this->resetValidation('upload');
    }

    protected function sectionUploadDraft(): VerificationTemplateVersion
    {
        abort_unless($this->isDraftEditingOpen(), 403);
        $draft = $this->getDisplayedClinicVersion();
        abort_unless($draft && $draft->id === $this->uploadVersionId && $this->getSelectedBuilderSection()['key'] === $this->uploadSectionKey, 409);
        return $draft;
    }

    public function reviewSectionUpload(): void
    {
        $draft = $this->sectionUploadDraft();
        $this->updatedUpload();
        $this->validate(['upload' => 'required|file|max:5120|mimes:xlsx,csv,txt']);
        $service = app(VerificationTemplateImport::class);
        $review = $service->read($this->upload->getRealPath(), $this->upload->getClientOriginalName());
        if (! $review['errors']) $review = $service->reviewSection($draft, $this->uploadSectionKey, $this->validBuilderFormType(), $review['rows']);
        $this->sectionUploadErrors = $review['errors'];
        $this->sectionUploadRows = $review['errors'] ? [] : $review['rows'];
    }

    public function saveSectionUpload(): void
    {
        $draft = $this->sectionUploadDraft();
        $this->validate(['confirmSectionUpload' => 'accepted']);
        abort_unless(count($this->sectionUploadRows) && ! $this->sectionUploadErrors, 422);
        app(VerificationTemplateImport::class)->appendToSection(auth()->user(), $draft, $this->uploadSectionKey, $this->validBuilderFormType(), $this->sectionUploadRows, $this->uploadToken);
        $this->showSectionUpload = false;
        $this->updatedUpload();
        $this->resetBuilderCaches();
        Notification::make()->title('Questions added to draft')->success()->send();
    }

    public function downloadSectionSample()
    {
        $draft = $this->sectionUploadDraft();
        $section = $draft->sections()->where('section_key', $this->uploadSectionKey)->firstOrFail();
        $parent = $section->parent_section_key ? $draft->sections()->where('section_key', $section->parent_section_key)->firstOrFail() : $section;
        $row = array_replace(array_fill_keys(VerificationTemplateImport::V3_HEADERS, ''), [
            'format_version' => '3', 'section_key' => $parent->section_key, 'section_name' => $parent->label,
            'section_order' => (string) $parent->sort_order,
            'subsection_key' => $section->parent_section_key ? $section->section_key : '',
            'subsection_name' => $section->parent_section_key ? $section->label : '',
            'subsection_order' => $section->parent_section_key ? (string) $section->sort_order : '',
            'question_order' => '10', 'question_key' => 'new_clinic_question', 'question' => 'New clinic question',
            'answer_type' => 'text', 'required_for_audit' => 'no', 'form_type' => $this->validBuilderFormType(), 'question_purpose' => 'other',
        ]);
        return response()->streamDownload(function () use ($row): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, VerificationTemplateImport::V3_HEADERS, ',', '"', '');
            fputcsv($stream, array_values($row), ',', '"', '');
            fclose($stream);
        }, 'section-questions.csv', ['Content-Type' => 'text/csv']);
    }

    public function selectBuilderForm(string $form): void
    {
        abort_unless(in_array($form, ['full_form', 'short_form'], true), 422);
        if ($this->guardEditor('selectBuilderForm', [$form])) return;
        $this->resetInlineEditor();
        $this->builderFormType = $form;
        $this->showDraft = false;
        $this->selectedTemplateVersionId = null;
        $this->selectedSectionKey = null;
        $this->builderView = 'questions';
        $this->data = $this->codeCoverageData = [];
        $this->showSectionUpload = false;
        $this->resetBuilderCaches();
    }

    public function selectBuilderVersion(int $id): void
    {
        if ($this->guardEditor('selectBuilderVersion', [$id])) return;
        $this->resetInlineEditor();
        $clinic = ClinicPanelScope::selectedClinic();
        abort_unless($clinic, 403);
        $version = VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('clinic_id', $clinic->id)->where('template_key', $this->selectedTemplateKey)
            ->whereIn('form_type', ['both', $this->validBuilderFormType()])->findOrFail($id);
        $this->selectedTemplateVersionId = $version->id;
        $this->showDraft = $version->canEditDirectly() && $this->canManageSelectedClinicTemplateSections();
        $this->selectedSectionKey = null;
        $this->builderView = 'questions';
        $this->data = $this->codeCoverageData = [];
        $this->showSectionUpload = false;
        $this->resetBuilderCaches();
    }

    protected function canManageSelectedClinicTemplateSections(): bool
    {
        return auth()->user()?->canManageClinicTemplateSections(ClinicPanelScope::selectedClinic()) ?? false;
    }

    public function getCreateUrl(?string $sectionKey = null): string
    {
        return VerificationQuestionResource::getUrl('create', array_filter([
            'section' => $sectionKey,
            'form' => $this->validBuilderFormType(),
            'template_version_id' => $this->getDraftClinicVersion()?->getKey(),
        ]));
    }

    public function getEditUrl(int $questionId): string
    {
        $question = VerificationFormQuestion::query()
            ->whereKey($questionId)
            ->where('clinic_id', ClinicPanelScope::selectedClinicId())
            ->firstOrFail();

        return VerificationQuestionResource::getUrl('edit', [
            'record' => $question->getRouteKey(),
            'form' => $this->validBuilderFormType(),
            'section' => $question->section_key,
            'template_version_id' => $question->template_version_id,
        ]);
    }

    public function updatedSelectedTemplateKey(): void
    {
        $this->selectedTemplateKey = VerificationFormQuestion::normalizeTemplateKey($this->selectedTemplateKey);
    }

    public function selectTemplate(string $templateKey): void
    {
        $templateKey = VerificationFormQuestion::normalizeTemplateKey($templateKey);

        if (! array_key_exists($templateKey, VerificationFormQuestion::templateOptionsForUi())) {
            return;
        }

        $this->selectedTemplateKey = $templateKey;
    }

    public function getSelectedTemplateLabel(): string
    {
        return 'Clinic Template';
    }

    public function getTemplateOptions(): array
    {
        return VerificationFormQuestion::templateOptionsForUi();
    }

    public function deleteQuestion(int $questionId): void
    {
        $clinicId = ClinicPanelScope::selectedClinicId();
        $version = $this->getDisplayedClinicVersion();

        if (! $clinicId || ! $this->isDraftEditingOpen() || $version?->status !== VerificationTemplateVersion::STATUS_DRAFT) {
            Notification::make()
                ->title('Create a draft first')
                ->body('Published clinic template versions are read-only. Create a draft before changing questions.')
                ->danger()
                ->send();

            return;
        }

        $question = VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->where('template_version_id', $version->getKey())
            ->find($questionId);

        if (! $question) {
            Notification::make()
                ->title('Question not found')
                ->danger()
                ->send();

            return;
        }

        if ($question->is_builtin) {
            Notification::make()
                ->title('SaaS system questions are protected')
                ->body('This clinic can reorder inherited questions, but only clinic-added questions can be deleted.')
                ->warning()
                ->send();

            return;
        }

        $question->delete();

        Notification::make()
            ->title('Question deleted')
            ->success()
            ->send();

        $this->normalizeSectionOrder((string) $question->section_key, (int) $clinicId);
    }

    public function repositionQuestion(int $questionId, string $direction): void
    {
        $clinicId = ClinicPanelScope::selectedClinicId();
        $organizationId = ClinicPanelScope::selectedOrganizationId();
        $version = $this->getDisplayedClinicVersion();

        if (! $clinicId || ! $this->isDraftEditingOpen() || $version?->status !== VerificationTemplateVersion::STATUS_DRAFT) {
            Notification::make()
                ->title('Create a draft first')
                ->body('Published clinic template versions are read-only. Create a draft before changing question order.')
                ->danger()
                ->send();

            return;
        }

        /** @var VerificationFormQuestion|null $question */
        $question = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->where('template_key', $this->selectedTemplateKey)
            ->where('template_version_id', $version->getKey())
            ->find($questionId);

        if (! $question) {
            Notification::make()
                ->title('Question not found')
                ->danger()
                ->send();

            return;
        }

        $questions = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->where('template_key', $question->template_key)
            ->where('template_version_id', $version->getKey())
            ->where('section_key', $question->section_key)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id']);

        $ids = $questions->pluck('id')->all();
        $currentIndex = array_search($questionId, $ids, true);

        if ($currentIndex === false) {
            return;
        }

        $newIds = $ids;

        switch ($direction) {
            case 'top':
                if ($currentIndex === 0) {
                    return;
                }

                unset($newIds[$currentIndex]);
                array_unshift($newIds, $questionId);
                break;

            case 'bottom':
                if ($currentIndex === count($ids) - 1) {
                    return;
                }

                unset($newIds[$currentIndex]);
                $newIds[] = $questionId;
                break;

            case 'up':
                if ($currentIndex === 0) {
                    return;
                }

                [$newIds[$currentIndex - 1], $newIds[$currentIndex]] = [$newIds[$currentIndex], $newIds[$currentIndex - 1]];
                break;

            case 'down':
                if ($currentIndex === count($ids) - 1) {
                    return;
                }

                [$newIds[$currentIndex + 1], $newIds[$currentIndex]] = [$newIds[$currentIndex], $newIds[$currentIndex + 1]];
                break;

            default:
                return;
        }

        $newIds = array_values(array_filter($newIds));

        DB::transaction(function () use ($newIds): void {
            foreach ($newIds as $index => $id) {
                VerificationFormQuestion::query()
                    ->whereKey($id)
                    ->update(['sort_order' => ($index + 1) * 10]);
            }
        });

        Notification::make()
            ->title('Question order updated')
            ->success()
            ->send();
    }

    protected function normalizeSectionOrder(string $sectionKey, int $clinicId): void
    {
        $organizationId = ClinicPanelScope::selectedOrganizationId();
        $version = $this->getDisplayedClinicVersion();

        $questions = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->where('template_key', $this->selectedTemplateKey)
            ->when($version, fn ($query) => $query->where('template_version_id', $version->getKey()))
            ->where('section_key', $sectionKey)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id']);

        foreach ($questions as $index => $question) {
            VerificationFormQuestion::query()
                ->whereKey($question->id)
                ->update(['sort_order' => ($index + 1) * 10]);
        }
    }

    public function getQuestionSections(): Collection
    {
        if ($this->questionSectionsCache !== null) {
            return $this->questionSectionsCache;
        }

        $clinicId = ClinicPanelScope::selectedClinicId();
        $organizationId = ClinicPanelScope::selectedOrganizationId();
        $version = $this->getDisplayedClinicVersion();

        if (! $clinicId || ! $version) {
            return $this->questionSectionsCache = collect();
        }

        $questions = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->where('template_key', $this->selectedTemplateKey)
            ->when($version, fn ($query) => $query->where('template_version_id', $version->getKey()))
            ->whereIn('form_type', ['both', $this->validBuilderFormType()])
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key');

        $sectionOptions = $version->uses_section_layout
            ? $version->sections()->orderBy('sort_order')->orderBy('id')->pluck('label', 'section_key')->all()
            : VerificationFormQuestion::sectionOptionsForTemplate($this->selectedTemplateKey, $clinicId, $version->id);

        return $this->questionSectionsCache = collect($sectionOptions)
            ->map(function (string $sectionTitle, string $sectionKey) use ($questions): array {
                $sectionQuestions = $questions->get($sectionKey, collect());
                $activeCount = $sectionQuestions->where('is_active', true)->count();
                $systemCount = $sectionQuestions->where('is_builtin', true)->count();

                return [
                    'key' => $sectionKey,
                    'title' => $sectionTitle,
                    'count' => $sectionQuestions->count(),
                    'active_count' => $activeCount,
                    'system_count' => $systemCount,
                    'questions' => $sectionQuestions->map(function (VerificationFormQuestion $question): array {
                        return [
                            'id' => $question->getKey(),
                            'prompt' => $question->prompt,
                            'is_active' => $question->is_active,
                            'is_builtin' => $question->is_builtin,
                            'form_type' => VerificationFormQuestion::FORM_TYPE_OPTIONS[$question->form_type] ?? str($question->form_type)->headline()->toString(),
                            'input_type' => VerificationFormQuestion::INPUT_TYPE_OPTIONS[$question->input_type] ?? str($question->input_type)->headline()->toString(),
                            'sort_order' => $question->sort_order,
                        ];
                    })->all(),
                ];
            });
    }

    public function selectBuilderSection(string $sectionKey): void
    {
        if ($this->guardEditor('selectBuilderSection', [$sectionKey])) return;
        $this->resetInlineEditor();
        $sectionExists = $this->getTemplateBuilderSections()
            ->flatMap(fn (array $section): array => [
                collect($section)->except('children')->all(),
                ...$section['children'],
            ])
            ->contains('key', $sectionKey);

        if (! $sectionExists) {
            return;
        }

        $this->selectedSectionKey = $sectionKey;
    }

    protected function redirectToQuestionCreate(): void
    {
        $sectionKey = $this->selectedSectionKey
            ?? $this->getSelectedBuilderSection()['key']
            ?? null;

        if (! $sectionKey) {
            Notification::make()
                ->title('Choose a section first')
                ->warning()
                ->send();

            return;
        }

        $this->redirect($this->getCreateUrl($sectionKey), navigate: true);
    }

    public function setBuilderView(string $view): void
    {
        if ($this->guardEditor('setBuilderView', [$view])) return;
        $this->resetInlineEditor();
        if (in_array($view, ['questions', 'reorder', 'preview'], true)) {
            if ($view === 'reorder' && ! $this->isDraftEditingOpen()) {
                $this->beginTemplateChange('reorder');

                return;
            }

            if ($view === 'preview') {
                foreach ($this->getDisplayedClinicVersion()?->questions()->where('input_type', 'frequency_row')->get() ?? [] as $question) {
                    $this->codeCoverageData[$question->id] ??= [
                        'code' => $question->code, 'description' => $question->prompt,
                        'category' => $question->frequencyCategory(),
                        'frequency_response_mode' => $question->frequency_response_mode,
                        'frequency_response_fields' => $question->frequency_response_fields,
                        'response_configuration' => $question->frequencyResponseConfiguration(),
                    ];
                }
            }
            $this->builderView = $view;
        }
    }

    public function getPreviewQuestions(): array
    {
        $version = $this->getDisplayedClinicVersion();
        if (! $version) return [];
        $all = $version->questions()->where('is_active', true)->whereIn('form_type', ['both', $this->validBuilderFormType()])->get();
        $sections = $version->sections()->get()->keyBy('section_key');
        return $all->sortBy(fn ($q) => sprintf('%08d-%08d-%08d', $sections->get($sections->get($q->section_key)?->parent_section_key)?->sort_order ?? $sections->get($q->section_key)?->sort_order ?? 0, $sections->get($q->section_key)?->sort_order ?? 0, $q->sort_order))->filter(function ($question) use ($all, $sections) {
            $section = $sections->get($question->section_key);
            if ($section && (! $section->is_active || ($section->parent_section_key && ! $sections->get($section->parent_section_key)?->is_active))) return false;
            return app(\App\Services\Verification\VerificationAuditService::class)->visibleInAnswerState($question, $all, $this->data);
        })->map(fn ($q) => [
            'id' => $q->id, 'type' => $q->input_type, 'label' => $q->prompt,
            'section' => $sections->get($q->section_key)?->label ?? $q->section_key,
            'field' => $q->is_builtin ? $q->field_key : 'custom_question_'.$q->id,
            'secondary_field' => $q->secondary_field_key, 'secondary_type' => $q->secondary_input_type,
            'options' => $q->getSelectOptionValues(), 'placeholder' => $q->placeholder,
            'required' => $q->is_required_for_audit, 'has_note' => $q->has_note,
            'note_field' => 'custom_question_note_'.$q->id, 'note_label' => $q->note_label ?: 'Note',
            'note_placeholder' => $q->note_placeholder, 'help_text' => $q->help_text,
        ])->values()->all();
    }

    public function clearQuestionFilters(): void
    {
        $this->questionSearch = '';
        $this->questionStatus = 'all';
        $this->questionOwnership = 'all';
    }

    public function getTemplateBuilderSections(): Collection
    {
        if ($this->templateBuilderSectionsCache !== null) {
            return $this->templateBuilderSectionsCache;
        }

        $sections = $this->getQuestionSections();
        $version = $this->getDisplayedClinicVersion();
        $clinicId = ClinicPanelScope::selectedClinicId();

        $parentMap = collect();

        if ($version) {
            $parentMap = VerificationTemplateSection::query()
                ->visibleForClinic($clinicId, ClinicPanelScope::selectedOrganizationId())
                ->where('template_key', $this->selectedTemplateKey)
                ->where('template_version_id', $version->getKey())
                ->pluck('parent_section_key', 'section_key');
        }

        $sections = $sections->map(function (array $section) use ($parentMap, $clinicId): array {
            $section['parent_key'] = $parentMap->has($section['key']) ? $parentMap->get($section['key'])
                : VerificationFormQuestion::parentSectionKeyFor(
                    $section['key'],
                    $this->selectedTemplateKey,
                    $clinicId,
                );

            return $section;
        });

        return $this->templateBuilderSectionsCache = $sections
            ->filter(fn (array $section): bool => blank($section['parent_key']))
            ->map(function (array $section) use ($sections): array {
                $children = $sections
                    ->filter(fn (array $candidate): bool => $candidate['parent_key'] === $section['key'])
                    ->values();

                $section['children'] = $children->all();
                $section['total_count'] = $section['count'] + $children->sum('count');
                $section['total_active_count'] = $section['active_count'] + $children->sum('active_count');

                return $section;
            })
            ->values();
    }

    public function getBuilderCounts(): array
    {
        $mainSections = $this->getTemplateBuilderSections();

        return [
            'main_sections' => $mainSections->count(),
            'sub_sections' => $mainSections->sum(fn (array $section): int => count($section['children'])),
            'questions' => $mainSections->sum('total_count'),
            'active_questions' => $mainSections->sum('total_active_count'),
        ];
    }

    public function getSelectedBuilderSection(): ?array
    {
        $sections = $this->getTemplateBuilderSections();
        $flat = $sections->flatMap(fn (array $section): array => [
            $section,
            ...$section['children'],
        ]);

        $selected = $flat->firstWhere('key', $this->selectedSectionKey)
            ?? $flat->first();

        return $selected;
    }

    public function getFilteredBuilderQuestions(): Collection
    {
        $selected = $this->getSelectedBuilderSection();

        if (! $selected) {
            return collect();
        }

        $questionGroups = collect([$selected]);

        $questions = $questionGroups->flatMap(function (array $section): array {
            return collect($section['questions'])
                ->map(function (array $question) use ($section): array {
                    $question['section_key'] = $section['key'];
                    $question['section_title'] = $section['title'];

                    return $question;
                })
                ->all();
        });

        $search = str($this->questionSearch)->trim()->lower()->toString();

        return $questions
            ->when($search !== '', fn (Collection $items): Collection => $items->filter(
                fn (array $question): bool => str($question['prompt'])->lower()->contains($search)
                    || str($question['section_title'])->lower()->contains($search)
            ))
            ->when($this->questionStatus === 'active', fn (Collection $items): Collection => $items->where('is_active', true))
            ->when($this->questionStatus === 'inactive', fn (Collection $items): Collection => $items->where('is_active', false))
            ->when($this->questionOwnership === 'system', fn (Collection $items): Collection => $items->where('is_builtin', true))
            ->when($this->questionOwnership === 'clinic', fn (Collection $items): Collection => $items->where('is_builtin', false))
            ->values();
    }

    protected function templateIdentifier(VerificationTemplateVersion $version): string
    {
        $publicId = strtoupper((string) $version->public_id);
        $suffix = filled($publicId)
            ? substr($publicId, -8)
            : str_pad((string) $version->getKey(), 8, '0', STR_PAD_LEFT);

        return 'CT-'.$suffix;
    }

    protected function clinicTemplateDisplayName(?string $name, ?string $clinicName, bool $draft = false): string
    {
        $fallback = trim(($clinicName ?: 'Clinic').' Clinic Template'.($draft ? ' Draft' : ''));
        $displayName = trim(str_replace(
            ['Master Template Draft', 'Master Template'],
            ['Clinic Template Draft', 'Clinic Template'],
            (string) $name,
        ));

        return $displayName !== '' ? $displayName : $fallback;
    }

    protected function getDisplayedClinicVersion(): ?VerificationTemplateVersion
    {
        if ($this->displayedVersionResolved) {
            return $this->displayedVersionCache;
        }

        $this->displayedVersionResolved = true;

        if (filled($this->selectedTemplateVersionId)) {
            $clinic = ClinicPanelScope::selectedClinic();
            $selectedVersion = $clinic
                ? VerificationTemplateVersion::query()
                    ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
                    ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
                    ->where('clinic_id', $clinic->getKey())
                    ->whereIn('form_type', ['both', $this->validBuilderFormType()])
                    ->whereKey($this->selectedTemplateVersionId)
                    ->first()
                : null;

            if ($selectedVersion) {
                return $this->displayedVersionCache = $selectedVersion;
            }
            abort(404);
        }

        if ($this->showDraft && ($draft = $this->getDraftClinicVersion())) {
            return $this->displayedVersionCache = $draft;
        }

        return $this->displayedVersionCache = $this->getActiveClinicVersion()
            ?? VerificationTemplateVersion::query()->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
                ->where('clinic_id', ClinicPanelScope::selectedClinicId())->where('template_key', $this->selectedTemplateKey)
                ->whereIn('form_type', ['both', $this->validBuilderFormType()])
                ->where('status', '!=', VerificationTemplateVersion::STATUS_ARCHIVED)
                ->orderByDesc('version_number')->orderByDesc('id')->first();
    }

    protected function isDraftEditingOpen(): bool
    {
        $draft = $this->getDraftClinicVersion();

        return $this->showDraft
            && $this->canManageSelectedClinicTemplateSections()
            && $draft?->canEditDirectly() === true;
    }

    protected function resetBuilderCaches(): void
    {
        $this->questionSectionsCache = null;
        $this->templateBuilderSectionsCache = null;
        $this->activeVersionResolved = false;
        $this->activeVersionCache = null;
        $this->draftVersionResolved = false;
        $this->draftVersionCache = null;
        $this->displayedVersionResolved = false;
        $this->displayedVersionCache = null;
    }
}
