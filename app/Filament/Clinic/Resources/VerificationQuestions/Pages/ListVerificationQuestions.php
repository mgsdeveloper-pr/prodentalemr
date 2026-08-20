<?php

namespace App\Filament\Clinic\Resources\VerificationQuestions\Pages;

use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateSection;
use App\Models\VerificationTemplateVersion;
use App\Support\ClinicPanelScope;
use App\Support\VerificationTemplateVersionService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListVerificationQuestions extends ListRecords
{
    protected static string $resource = VerificationQuestionResource::class;

    protected string $view = 'filament.clinic.resources.verification-questions.pages.list-verification-questions';

    public string $selectedTemplateKey = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY;

    public bool $showDraft = false;

    public bool $showCreateDraftModal = false;

    public bool $showTemplateSectionModal = false;

    public string $templateSectionMode = 'section';

    public array $newDraftData = [
        'template_name' => '',
        'form_type' => VerificationTemplateVersion::FORM_TYPE_BOTH,
        'clinic_visibility' => VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE,
    ];

    public array $newTemplateSectionData = [
        'label' => '',
        'parent_section_key' => null,
    ];

    protected $queryString = [
        'selectedTemplateKey' => ['except' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY, 'as' => 'template'],
        'showDraft' => ['except' => false, 'as' => 'draft'],
    ];

    public function getTitle(): string
    {
        return 'Clinic Template';
    }

    public function getHeading(): string
    {
        return 'Clinic Template';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getVisibleHeaderActions(): array
    {
        return [];
    }

    public function openCreateDraftModal(): void
    {
        if (! $this->canManageSelectedClinicTemplateSections()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return;
        }

        $clinicName = $this->getSelectedClinicName() ?: 'Clinic';

        $this->newDraftData = [
            'template_name' => $clinicName . ' Template Draft',
            'form_type' => VerificationTemplateVersion::FORM_TYPE_BOTH,
            'clinic_visibility' => VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE,
        ];
        $this->showCreateDraftModal = true;
    }

    public function closeCreateDraftModal(): void
    {
        $this->showCreateDraftModal = false;
    }

    public function submitCreateDraftVersion(): void
    {
        $data = $this->validate([
            'newDraftData.template_name' => ['required', 'string', 'max:255'],
            'newDraftData.form_type' => ['required', 'string'],
            'newDraftData.clinic_visibility' => ['required', 'string'],
        ])['newDraftData'];

        $this->createDraftVersion($data);
        $this->closeCreateDraftModal();
    }

    public function openTemplateSectionModal(string $mode = 'section'): void
    {
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
            $sectionKey = $baseKey . '_' . $counter++;
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

        return [
            'clinic_name' => $clinic?->clinic_name,
            'active_version' => $active ? 'v' . $active->version_number : 'None',
            'active_name' => $active?->name ?? 'No active clinic template',
            'active_published_at' => optional($active?->published_at)->format('M d, Y h:i A') ?: 'Not published',
            'active_form_type' => VerificationTemplateVersion::FORM_TYPE_OPTIONS[$active?->form_type] ?? 'Full + Short',
            'active_visibility' => 'Clinic Copy',
            'working_version' => $draft ? 'v' . $draft->version_number : 'No draft',
            'working_name' => $draft?->name ?? 'Clinic Template Draft',
            'working_status' => $draft ? 'Draft' : 'Create a draft to edit',
            'working_form_type' => VerificationTemplateVersion::FORM_TYPE_OPTIONS[$draft?->form_type] ?? 'Full + Short',
            'working_visibility' => $draft ? 'Clinic Draft' : 'Read-only',
            'has_draft' => (bool) $draft,
            'showing_draft' => $this->showDraft && (bool) $draft,
            'draft_version' => $draft ? 'v' . $draft->version_number : null,
            'can_manage' => $this->canManageSelectedClinicTemplateSections(),
            'manager_edits_enabled' => $clinic?->allowsVerificationManagerTemplateEdits() ?? false,
        ];
    }

    public function openDraftVersion(): null
    {
        if (! $this->getDraftClinicVersion()) {
            Notification::make()->title('No draft version found')->warning()->send();

            return null;
        }

        $this->showDraft = true;

        return null;
    }

    public function closeDraftVersion(): null
    {
        $this->showDraft = false;

        return null;
    }

    public function createDraftVersion(array $data = []): null
    {
        if (! $this->canManageSelectedClinicTemplateSections()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return null;
        }

        $published = $this->getActiveClinicVersion();

        if (! $published) {
            Notification::make()->title('Select a clinic first')->danger()->send();

            return null;
        }

        $draft = app(VerificationTemplateVersionService::class)->createDraftFromPublished($published);
        $draft->forceFill([
            'name' => $data['template_name'] ?? $draft->name,
            'form_type' => $data['form_type'] ?? $draft->form_type,
            'clinic_visibility' => $data['clinic_visibility'] ?? $draft->clinic_visibility,
        ])->save();
        $this->showDraft = true;

        Notification::make()
            ->title('Draft version ready')
            ->body('You are now editing version ' . $draft->version_number . ' for ' . ($published->clinic?->clinic_name ?? 'this clinic') . '.')
            ->success()
            ->send();

        return null;
    }

    public function publishDraftVersion(array $data = []): null
    {
        if (! $this->canManageSelectedClinicTemplateSections()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return null;
        }

        $draft = $this->getDraftClinicVersion();

        if (! $draft) {
            Notification::make()->title('No draft version found')->warning()->send();

            return null;
        }

        $published = app(VerificationTemplateVersionService::class)->publishDraft(
            $draft,
            $data['version_name'] ?? null,
            $data['change_description'] ?? null,
        );
        $this->showDraft = false;

        Notification::make()
            ->title('Clinic template published')
            ->body('Version ' . $published->version_number . ' is now active for this clinic.')
            ->success()
            ->send();

        return null;
    }

    public function getActiveClinicVersion(): ?VerificationTemplateVersion
    {
        $clinic = ClinicPanelScope::selectedClinic();

        return $clinic ? app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($clinic) : null;
    }

    public function getDraftClinicVersion(): ?VerificationTemplateVersion
    {
        $clinic = ClinicPanelScope::selectedClinic();

        if (! $clinic) {
            return null;
        }

        return VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
            ->where('clinic_id', $clinic->getKey())
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
            ->orderByDesc('version_number')
            ->orderByDesc('id')
            ->get()
            ->map(fn (VerificationTemplateVersion $version): array => [
                'name' => $version->name,
                'version' => 'v' . $version->version_number,
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

    protected function canManageSelectedClinicTemplateSections(): bool
    {
        return auth()->user()?->canManageClinicTemplateSections(ClinicPanelScope::selectedClinic()) ?? false;
    }

    public function getCreateUrl(): string
    {
        return VerificationQuestionResource::getUrl('create');
    }

    public function getEditUrl(int $questionId): string
    {
        return VerificationQuestionResource::getUrl('edit', ['record' => $questionId]);
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
        $clinicId = ClinicPanelScope::selectedClinicId();
        $organizationId = ClinicPanelScope::selectedOrganizationId();
        $version = $this->getDisplayedClinicVersion();

        if (! $clinicId) {
            return collect();
        }

        $questions = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->where('template_key', $this->selectedTemplateKey)
            ->when($version, fn ($query) => $query->where('template_version_id', $version->getKey()))
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key');

        return collect(VerificationFormQuestion::sectionOptionsForTemplate($this->selectedTemplateKey, $clinicId))
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

    protected function getDisplayedClinicVersion(): ?VerificationTemplateVersion
    {
        if ($this->showDraft && $this->getDraftClinicVersion()) {
            return $this->getDraftClinicVersion();
        }

        return $this->getActiveClinicVersion();
    }

    protected function isDraftEditingOpen(): bool
    {
        return $this->showDraft
            && $this->canManageSelectedClinicTemplateSections()
            && (bool) $this->getDraftClinicVersion();
    }
}
