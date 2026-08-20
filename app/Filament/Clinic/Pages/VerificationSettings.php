<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Filament\Clinic\Resources\PortalCredentials\PortalCredentialResource;
use App\Actions\Verification\ArchiveClinicTemplateVersionAction;
use App\Actions\Verification\CreateClinicTemplateDraftAction;
use App\Actions\Verification\PublishClinicTemplateDraftAction;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationPdfPreset;
use App\Models\VerificationTemplateVersion;
use App\Services\Verification\PdfPresetService;
use App\Support\ClinicPanelScope;
use App\Support\VerificationResultPdf;
use App\Support\VerificationTemplateVersionService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;
use UnitEnum;

class VerificationSettings extends Page
{
    protected const PDF_SECTION_LABELS = [
        'core_details' => 'Core Eligibility',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Verification Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Verification Settings';

    protected static ?string $slug = 'verification-settings';

    protected string $view = 'filament.clinic.pages.verification-settings';

    public ?array $data = [];

    public string $activeSettingsSection = 'template-selection';

    public bool $showCreateTemplateDraftModal = false;

    public array $newClinicTemplateDraftData = [
        'template_name' => 'Clinic Template Draft',
        'form_type' => 'both',
        'starting_point' => 'active',
        'source_version_id' => null,
    ];

    protected $queryString = [
        'activeSettingsSection' => ['as' => 'section', 'except' => 'template-selection'],
    ];

    protected ?Clinic $clinicRecord = null;

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageClinicVerificationSettings() ?? false;
    }

    public function mount(): void
    {
        $this->clinicRecord = $this->resolveClinic();
        if ($this->clinicRecord) {
            app(PdfPresetService::class)->seedDefaultsForClinic($this->clinicRecord);
            $this->clinicRecord->refresh();
        }

        $this->fillStateFromClinic($this->clinicRecord);

        if (! in_array($this->activeSettingsSection, $this->settingsSectionKeys(), true)) {
            $this->activeSettingsSection = 'template-selection';
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function showSettingsSection(string $section): void
    {
        if (! in_array($section, $this->settingsSectionKeys(), true)) {
            return;
        }

        $this->activeSettingsSection = $section;
        $this->dispatch('verification-settings-section-changed');
    }

    public function createNewPreset(): void
    {
        $this->data['verification_pdf_preset_id'] = null;
        $this->data['verification_pdf_preset_name'] = 'Custom PDF Preset';
        $this->data['verification_pdf_preset_description'] = null;
        $this->data['verification_pdf_preset_is_default'] = false;
        $this->data['verification_pdf_show_blank_rows'] = false;

        Notification::make()
            ->title('New preset ready')
            ->body('Name the preset, choose sections/questions, then save it.')
            ->success()
            ->send();
    }

    public function loadPreset(int|string|null $presetId): void
    {
        $presetId = (int) $presetId;

        if ($presetId <= 0) {
            return;
        }

        $clinic = $this->resolveClinic();

        if (! $clinic) {
            return;
        }

        $preset = app(PdfPresetService::class)
            ->queryForClinic($clinic)
            ->whereKey($presetId)
            ->first();

        if (! $preset) {
            return;
        }

        $questionIds = $preset->getQuestionIds();
        $this->data['verification_pdf_preset_id'] = $preset->getKey();
        $this->data['verification_pdf_preset_name'] = $preset->name;
        $this->data['verification_pdf_preset_description'] = $preset->description;
        $this->data['verification_pdf_preset_is_default'] = $preset->is_default;
        $this->data['verification_pdf_output_mode'] = $preset->getOutputMode();
        $this->data['verification_pdf_output_sections'] = $preset->getSectionKeys();
        $this->data['verification_pdf_output_question_ids'] = $questionIds;
        $this->data['verification_pdf_output_question_ids_by_section'] = $this->groupQuestionIdsBySection($questionIds);
        $this->data['verification_pdf_show_blank_rows'] = $preset->shouldShowBlankRows();
    }

    public function save(): void
    {
        $clinic = $this->resolveClinic();

        if (! $clinic) {
            Notification::make()
                ->title('Select a clinic first')
                ->body('Choose a clinic from the Workspace menu before changing verification settings.')
                ->danger()
                ->send();

            return;
        }

        $this->syncFlattenedQuestionIds();

        $state = $this->data;
        $mode = $state['verification_pdf_output_mode'] ?? 'standard';
        $sections = is_array($state['verification_pdf_output_sections'] ?? null)
            ? $state['verification_pdf_output_sections']
            : [];
        $groupedQuestionIds = is_array($state['verification_pdf_output_question_ids_by_section'] ?? null)
            ? $state['verification_pdf_output_question_ids_by_section']
            : [];

        $isCustomMode = VerificationResultPdf::isCustomOutputMode($mode);
        $groupedQuestionIds = $isCustomMode
            ? $this->normalizeGroupedQuestionIds($sections, $groupedQuestionIds)
            : [];
        $questionIds = $this->flattenGroupedQuestionIds($groupedQuestionIds);

        if ($isCustomMode && empty($sections)) {
            Notification::make()
                ->title('Select at least one section')
                ->body('Choose the verification sections that should appear in the selected output PDF.')
                ->danger()
                ->send();

            return;
        }

        $preset = filled($state['verification_pdf_preset_id'] ?? null)
            ? app(PdfPresetService::class)->queryForClinic($clinic)->whereKey($state['verification_pdf_preset_id'])->first()
            : null;

        $savedPreset = app(PdfPresetService::class)->saveForClinic($clinic, [
            'name' => $state['verification_pdf_preset_name'] ?? 'Verification PDF Preset',
            'description' => $state['verification_pdf_preset_description'] ?? null,
            'output_mode' => $mode,
            'section_keys' => $isCustomMode ? array_values($sections) : [],
            'question_ids' => $isCustomMode ? array_values($questionIds) : [],
            'show_blank_rows' => (bool) ($state['verification_pdf_show_blank_rows'] ?? ($mode === 'standard')),
            'is_default' => (bool) ($state['verification_pdf_preset_is_default'] ?? true),
        ], $preset);

        $selectedTemplateVersion = $this->findSelectableClinicTemplateVersion(
            (int) ($state['verification_template_version_id'] ?? 0)
        );

        if (filled($state['verification_template_version_id'] ?? null) && ! $selectedTemplateVersion) {
            Notification::make()
                ->title('Template not available')
                ->body('Choose a published clinic template from the list before saving.')
                ->danger()
                ->send();

            return;
        }

        if ($selectedTemplateVersion) {
            $this->activateClinicTemplateVersion($selectedTemplateVersion);
        }

        $clinic->update([
            'verification_default_form_template' => $selectedTemplateVersion?->template_key
                ?? $state['verification_default_form_template']
                ?? VerificationFormQuestion::defaultTemplateKey(),
            'allow_verification_manager_template_edits' => (bool) ($state['allow_verification_manager_template_edits'] ?? false),
            'default_verification_pdf_preset_id' => ($state['verification_pdf_preset_is_default'] ?? true) ? $savedPreset->getKey() : $clinic->default_verification_pdf_preset_id,
        ]);

        $this->clinicRecord = $clinic->fresh('organization');
        $selectedQuestionIds = $savedPreset->getQuestionIds();
        $this->data = $this->stateFromPreset($this->clinicRecord, $savedPreset, $selectedQuestionIds);

        Notification::make()
            ->title('Verification settings saved')
            ->body('The clinic template and PDF settings have been updated successfully.')
            ->success()
            ->send();
    }

    public function getSelectedClinic(): ?Clinic
    {
        return $this->resolveClinic();
    }

    protected function settingsSectionKeys(): array
    {
        return [
            'template-selection',
            'template-management',
            'pdf-settings',
        ];
    }

    protected function fillStateFromClinic(?Clinic $clinic): void
    {
        if (! $clinic) {
            $this->data = $this->emptyState();

            return;
        }

        $preset = app(PdfPresetService::class)->defaultForClinic($clinic);
        $selectedQuestionIds = $preset?->getQuestionIds() ?? $clinic->getVerificationPdfOutputQuestionIds();

        $this->data = $this->stateFromPreset($clinic, $preset, $selectedQuestionIds);
    }

    protected function stateFromPreset(Clinic $clinic, ?VerificationPdfPreset $preset, array $selectedQuestionIds): array
    {
        $mode = VerificationResultPdf::normalizeOutputMode(
            $preset?->getOutputMode() ?? $clinic->getVerificationPdfOutputMode()
        );

        return [
            'verification_pdf_preset_id' => $preset?->getKey(),
            'verification_pdf_preset_name' => $preset?->name ?? 'Full Verification Report',
            'verification_pdf_preset_description' => $preset?->description,
            'verification_pdf_preset_is_default' => (bool) ($preset?->is_default ?? true),
            'verification_default_form_template' => $clinic->getVerificationDefaultFormTemplate(),
            'verification_template_version_id' => $this->selectedClinicTemplateVersionId($clinic),
            'verification_pdf_output_mode' => $mode,
            'verification_pdf_output_sections' => $preset?->getSectionKeys() ?? $clinic->getVerificationPdfOutputSections(),
            'verification_pdf_output_question_ids' => $selectedQuestionIds,
            'verification_pdf_output_question_ids_by_section' => $this->groupQuestionIdsBySection($selectedQuestionIds),
            'verification_pdf_show_blank_rows' => $preset?->shouldShowBlankRows() ?? ! VerificationResultPdf::isCustomOutputMode($mode),
            'allow_verification_manager_template_edits' => $clinic->allowsVerificationManagerTemplateEdits(),
        ];
    }

    protected function emptyState(): array
    {
        return [
            'verification_pdf_preset_id' => null,
            'verification_pdf_preset_name' => 'Full Verification Report',
            'verification_pdf_preset_description' => null,
            'verification_pdf_preset_is_default' => true,
            'verification_default_form_template' => VerificationFormQuestion::defaultTemplateKey(),
            'verification_template_version_id' => null,
            'verification_pdf_output_mode' => 'standard',
            'verification_pdf_output_sections' => [],
            'verification_pdf_output_question_ids' => [],
            'verification_pdf_output_question_ids_by_section' => [],
            'verification_pdf_show_blank_rows' => true,
            'allow_verification_manager_template_edits' => false,
        ];
    }

    public function getManageQuestionsUrl(): string
    {
        return VerificationQuestionResource::getUrl('index', panel: 'clinic');
    }

    public function getReorderQuestionsUrl(): string
    {
        return VerificationQuestionResource::getUrl('reorder', panel: 'clinic');
    }

    public function getCreateQuestionUrl(): string
    {
        return VerificationQuestionResource::getUrl('create', panel: 'clinic');
    }

    public function getPortalCredentialsUrl(): string
    {
        return PortalCredentialResource::getUrl('index', panel: 'clinic');
    }

    public function createClinicTemplateDraft(): void
    {
        $this->openCreateClinicTemplateDraftModal();
    }

    public function openCreateClinicTemplateDraftModal(): void
    {
        if (! $this->canManageSelectedClinicTemplate()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return;
        }

        $clinic = $this->resolveClinic();

        if (! $clinic) {
            Notification::make()->title('Select a clinic first')->danger()->send();

            return;
        }

        $this->resetNewClinicTemplateDraftData();
        $this->showCreateTemplateDraftModal = true;
    }

    public function closeCreateTemplateDraftModal(): void
    {
        $this->showCreateTemplateDraftModal = false;
        $this->resetErrorBag();
    }

    public function submitCreateClinicTemplateDraft(): void
    {
        if (! $this->canManageSelectedClinicTemplate()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return;
        }

        $clinic = $this->resolveClinic();

        if (! $clinic) {
            Notification::make()->title('Select a clinic first')->danger()->send();

            return;
        }

        $validated = $this->validate([
            'newClinicTemplateDraftData.template_name' => ['required', 'string', 'max:255'],
            'newClinicTemplateDraftData.form_type' => ['required', 'in:' . implode(',', array_keys(VerificationTemplateVersion::FORM_TYPE_OPTIONS))],
            'newClinicTemplateDraftData.starting_point' => ['required', 'in:active,fresh,specific_version'],
            'newClinicTemplateDraftData.source_version_id' => ['nullable', 'integer'],
        ]);

        $data = $validated['newClinicTemplateDraftData'];
        $startingPoint = $data['starting_point'] ?? 'active';
        $source = match ($startingPoint) {
            'fresh' => null,
            'specific_version' => $this->findSelectedClinicTemplateVersion((int) ($data['source_version_id'] ?? 0)),
            default => $this->getActiveClinicTemplateVersion(),
        };

        if ($startingPoint === 'specific_version' && ! $source) {
            Notification::make()
                ->title('Select a template to copy')
                ->body('Choose the existing clinic template this draft should replicate.')
                ->warning()
                ->send();

            return;
        }

        if ($startingPoint === 'active' && ! $source) {
            Notification::make()
                ->title('No active template found')
                ->body('Publish or select an active clinic template before copying it.')
                ->warning()
                ->send();

            return;
        }

        try {
            $draft = app(CreateClinicTemplateDraftAction::class)->execute(
                auth()->user(),
                $clinic,
                $source,
                $data,
            );
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Draft could not be created')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->showCreateTemplateDraftModal = false;
        $this->activeSettingsSection = 'template-management';

        Notification::make()
            ->title('Draft template ready')
            ->body($this->clinicTemplateDisplayName($draft) . ' is ready for editing.')
            ->success()
            ->send();
    }

    public function clinicTemplateDraftSourceOptions(): array
    {
        $clinic = $this->resolveClinic();

        if (! $clinic) {
            return [];
        }

        return VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('clinic_id', $clinic->getKey())
            ->where('status', '!=', VerificationTemplateVersion::STATUS_ARCHIVED)
            ->orderByDesc('version_number')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function (VerificationTemplateVersion $version): array {
                $label = $this->clinicTemplateIdentifier($version)
                    . ' - ' . str($version->status)->headline()
                    . ' - ' . $this->clinicTemplateDisplayName($version);

                return [$version->getKey() => $label];
            })
            ->all();
    }

    protected function resetNewClinicTemplateDraftData(): void
    {
        $clinic = $this->resolveClinic();
        $defaultName = trim((string) ($clinic?->clinic_name ?: 'Clinic')) . ' Template Draft';

        $this->newClinicTemplateDraftData = [
            'template_name' => $defaultName,
            'form_type' => VerificationTemplateVersion::FORM_TYPE_BOTH,
            'starting_point' => 'active',
            'source_version_id' => null,
        ];
    }

    public function publishClinicTemplateDraft(): void
    {
        if (! $this->canManageSelectedClinicTemplate()) {
            Notification::make()->title('Permission denied')->danger()->send();

            return;
        }

        $draft = $this->getDraftClinicTemplateVersion();

        if (! $draft) {
            Notification::make()->title('No draft template found')->warning()->send();

            return;
        }

        $clinic = $this->resolveClinic();

        if (! $clinic) {
            Notification::make()->title('Select a clinic first')->danger()->send();

            return;
        }

        try {
            $published = app(PublishClinicTemplateDraftAction::class)->execute(
                auth()->user(),
                $clinic,
                $draft,
            );
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Template could not be published')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }
        $this->activeSettingsSection = 'template-management';

        Notification::make()
            ->title('Clinic template published')
            ->body($this->clinicTemplateDisplayName($published) . ' is now active for this clinic.')
            ->success()
            ->send();
    }

    public function archiveClinicTemplateVersion(int $versionId): void
    {
        $version = $this->findSelectedClinicTemplateVersion($versionId);
        $clinic = $this->resolveClinic();

        if (! $version || ! $clinic || ! $this->canArchiveClinicTemplateVersion($version)) {
            Notification::make()
                ->title('Template cannot be archived')
                ->body('Only clinic-created templates that are not active and not used by verification requests can be archived.')
                ->warning()
                ->send();

            return;
        }

        try {
            app(ArchiveClinicTemplateVersionAction::class)->execute(
                auth()->user(),
                $clinic,
                $version,
            );
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Template cannot be archived')
                ->body($exception->getMessage())
                ->warning()
                ->send();

            return;
        }

        $this->activeSettingsSection = 'template-management';

        Notification::make()
            ->title('Template archived')
            ->body($version->name . ' was removed from the active clinic template list.')
            ->success()
            ->send();
    }

    public function getClinicTemplateVersionRows(): array
    {
        $clinic = $this->resolveClinic();

        if (! $clinic) {
            return [];
        }

        app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($clinic);
        $this->enforceSingleActiveClinicTemplate($clinic);

        return VerificationTemplateVersion::query()
            ->withCount([
                'questions',
                'questions as active_questions_count' => fn ($query) => $query->where('is_active', true),
                'sections',
            ])
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('clinic_id', $clinic->getKey())
            ->where('status', '!=', VerificationTemplateVersion::STATUS_ARCHIVED)
            ->orderByDesc('is_working_draft')
            ->orderByDesc('is_active')
            ->orderByDesc('version_number')
            ->orderByDesc('id')
            ->get()
            ->map(function (VerificationTemplateVersion $version): array {
                $usedRequestCount = $this->templateVersionUsageCount($version);
                $canArchive = $this->canArchiveClinicTemplateVersion($version, $usedRequestCount);
                $questionCount = (int) $version->questions_count;
                $activeQuestionCount = (int) $version->active_questions_count;
                $sectionCounts = $this->approvedTemplateThreeSectionCounts($version, $questionCount);

                return [
                    'id' => (int) $version->getKey(),
                    'template_id' => $this->clinicTemplateIdentifier($version),
                    'name' => $this->clinicTemplateDisplayName($version),
                    'version' => 'v' . $version->version_number,
                    'row_group' => $this->clinicTemplateRowGroup($version),
                    'status' => $this->clinicTemplateStatusLabel($version),
                    'status_key' => $version->status,
                    'form_type' => VerificationTemplateVersion::FORM_TYPE_OPTIONS[$version->form_type] ?? 'Full + Short',
                    'visibility' => $this->clinicTemplateVisibilityLabel($version),
                    'questions' => $questionCount,
                    'active_questions' => $activeQuestionCount,
                    'sections' => $sectionCounts['main'],
                    'sub_sections' => $sectionCounts['sub'],
                    'updated_at' => optional($version->updated_at)->format('M d, Y h:i A') ?: '-',
                    'published_at' => optional($version->published_at)->format('M d, Y h:i A') ?: '-',
                    'used_request_count' => $usedRequestCount,
                    'archive_block_reason' => $this->archiveBlockReason($version, $usedRequestCount),
                    'is_active' => (bool) $version->is_active,
                    'is_working_draft' => (bool) $version->is_working_draft,
                    'is_draft' => $version->status === VerificationTemplateVersion::STATUS_DRAFT,
                    'can_edit' => $this->canEditClinicTemplateVersion($version),
                    'can_archive' => $canArchive,
                ];
            })
            ->sortBy(fn (array $row): string => match ($row['row_group']) {
                'active' => '1-' . $row['version'],
                'draft' => '2-' . $row['version'],
                default => '3-' . $row['version'],
            })
            ->values()
            ->all();
    }

    protected function enforceSingleActiveClinicTemplate(Clinic $clinic): void
    {
        $activeVersions = VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('clinic_id', $clinic->getKey())
            ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
            ->where('is_active', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        $activeVersions
            ->skip(1)
            ->each(fn (VerificationTemplateVersion $version): bool => $version->forceFill([
                'is_active' => false,
                'is_working_draft' => false,
            ])->save());
    }

    protected function clinicTemplateStatusLabel(VerificationTemplateVersion $version): string
    {
        if ($version->status === VerificationTemplateVersion::STATUS_DRAFT) {
            return 'Draft';
        }

        if ($version->status === VerificationTemplateVersion::STATUS_PUBLISHED && ! $version->is_active) {
            return 'Not Active';
        }

        return str($version->status)->headline()->toString();
    }

    protected function canEditClinicTemplateVersion(VerificationTemplateVersion $version): bool
    {
        if (! $this->canManageSelectedClinicTemplate()) {
            return false;
        }

        return $version->status === VerificationTemplateVersion::STATUS_DRAFT
            || ($version->status === VerificationTemplateVersion::STATUS_PUBLISHED && (bool) $version->is_active);
    }

    protected function clinicTemplateDisplayName(VerificationTemplateVersion $version): string
    {
        $name = trim((string) $version->name);

        if ($version->status === VerificationTemplateVersion::STATUS_DRAFT) {
            return str($name)
                ->replace('Master Template Draft', 'Clinic Template Draft')
                ->replace('Master Template', 'Clinic Template')
                ->toString() ?: 'Clinic Template Draft';
        }

        return str($name)
            ->replace('Master Template', 'Clinic Template')
            ->toString() ?: 'Clinic Template';
    }

    protected function clinicTemplateIdentifier(VerificationTemplateVersion $version): string
    {
        $publicId = strtoupper((string) $version->public_id);
        $suffix = filled($publicId) ? substr($publicId, -8) : str_pad((string) $version->getKey(), 8, '0', STR_PAD_LEFT);

        return 'CT-' . $suffix;
    }

    protected function clinicTemplateRowGroup(VerificationTemplateVersion $version): string
    {
        if ($version->status === VerificationTemplateVersion::STATUS_DRAFT) {
            return 'draft';
        }

        return $version->is_active ? 'active' : 'previous';
    }

    protected function clinicTemplateVisibilityLabel(VerificationTemplateVersion $version): string
    {
        if ($version->status === VerificationTemplateVersion::STATUS_DRAFT) {
            return 'Clinic Draft';
        }

        return $version->is_active ? 'Active Clinic Copy' : 'Template History';
    }

    protected function approvedTemplateThreeSectionCounts(VerificationTemplateVersion $version, int $questionCount): array
    {
        if ($questionCount === 0 && (int) $version->sections_count === 0) {
            return ['main' => 0, 'sub' => 0];
        }

        return [
            'main' => count($this->approvedTemplateThreeMainSectionKeys()),
            'sub' => count($this->approvedTemplateThreeSubSectionKeys()),
        ];
    }

    protected function approvedTemplateThreeMainSectionKeys(): array
    {
        return [
            'template_3_patient_subscriber',
            'template_3_insurance',
            'template_3_maximums_deductibles',
            'template_3_coverage_category',
            'template_3_plan_provisions',
            'template_3_service_history',
            'template_3_frequency_percentage',
            'template_3_verification_information',
        ];
    }

    protected function approvedTemplateThreeSubSectionKeys(): array
    {
        return [
            'template_3_frequency_diagnostic_preventative',
            'template_3_frequency_basic',
            'template_3_frequency_major',
            'template_3_frequency_orthodontics',
        ];
    }

    protected function findSelectedClinicTemplateVersion(int $versionId): ?VerificationTemplateVersion
    {
        $clinic = $this->resolveClinic();

        if (! $clinic) {
            return null;
        }

        return VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('clinic_id', $clinic->getKey())
            ->whereKey($versionId)
            ->first();
    }

    protected function findSelectableClinicTemplateVersion(int $versionId): ?VerificationTemplateVersion
    {
        if ($versionId <= 0) {
            return null;
        }

        $clinic = $this->resolveClinic();

        if (! $clinic) {
            return null;
        }

        return VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('clinic_id', $clinic->getKey())
            ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
            ->where('clinic_visibility', '!=', VerificationTemplateVersion::CLINIC_VISIBILITY_RETIRED)
            ->whereKey($versionId)
            ->first();
    }

    protected function activateClinicTemplateVersion(VerificationTemplateVersion $version): void
    {
        DB::transaction(function () use ($version): void {
            VerificationTemplateVersion::query()
                ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
                ->where('template_key', $version->template_key)
                ->where('clinic_id', $version->clinic_id)
                ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
                ->update([
                    'is_active' => false,
                    'is_working_draft' => false,
                ]);

            $version->forceFill([
                'is_active' => true,
                'is_working_draft' => false,
            ])->save();
        });
    }

    protected function canArchiveClinicTemplateVersion(VerificationTemplateVersion $version, ?int $usedRequestCount = null): bool
    {
        $usedRequestCount ??= $this->templateVersionUsageCount($version);

        return $this->canManageSelectedClinicTemplate()
            && $version->scope === VerificationTemplateVersion::SCOPE_CLINIC
            && ! $version->is_active
            && $version->status !== VerificationTemplateVersion::STATUS_ARCHIVED
            && $usedRequestCount === 0;
    }

    protected function archiveBlockReason(VerificationTemplateVersion $version, int $usedRequestCount): ?string
    {
        if ($version->scope !== VerificationTemplateVersion::SCOPE_CLINIC) {
            return 'SaaS master template cannot be archived from clinic settings.';
        }

        if ($version->is_active) {
            return 'Active clinic template cannot be archived.';
        }

        if ($usedRequestCount > 0) {
            return $usedRequestCount . ' verification request(s) use this template.';
        }

        if (! $this->canManageSelectedClinicTemplate()) {
            return 'You do not have permission to archive this template.';
        }

        return null;
    }

    protected function templateVersionUsageCount(VerificationTemplateVersion $version): int
    {
        return BillingWorkItem::query()
            ->where('verification_template_version_id', $version->getKey())
            ->count();
    }

    public function getActiveClinicTemplateVersion(): ?VerificationTemplateVersion
    {
        $clinic = $this->resolveClinic();

        return $clinic ? app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($clinic) : null;
    }

    protected function selectedClinicTemplateVersionId(Clinic $clinic): ?int
    {
        $activeVersion = VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
            ->where('clinic_id', $clinic->getKey())
            ->where('is_active', true)
            ->latest('id')
            ->first();

        $version = $activeVersion
            ?? app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($clinic);

        return $version?->getKey();
    }

    public function getDraftClinicTemplateVersion(): ?VerificationTemplateVersion
    {
        $clinic = $this->resolveClinic();

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

    public function canManageSelectedClinicTemplate(): bool
    {
        return auth()->user()?->canManageClinicTemplateSections($this->resolveClinic()) ?? false;
    }

    public function getTemplateOptions(): array
    {
        return VerificationFormQuestion::ACTIVE_TEMPLATE_OPTIONS;
    }

    public function getClinicTemplateOptions(): array
    {
        $clinic = $this->resolveClinic();

        if (! $clinic) {
            return [];
        }

        app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($clinic);

        return VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
            ->where('clinic_id', $clinic->getKey())
            ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
            ->where('clinic_visibility', '!=', VerificationTemplateVersion::CLINIC_VISIBILITY_RETIRED)
            ->orderByDesc('is_active')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function (VerificationTemplateVersion $version): array {
                $label = $this->clinicTemplateDisplayName($version)
                    . ' (' . $this->clinicTemplateIdentifier($version) . ')';

                if ($version->is_active) {
                    $label .= ' - Active';
                }

                return [$version->getKey() => $label];
            })
            ->all();
    }

    public function getPreviewPdfUrl(): ?string
    {
        $clinic = $this->resolveClinic();

        if (! $clinic) {
            return null;
        }

        $request = BillingWorkItem::query()
            ->where('clinic_id', $clinic->getKey())
            ->latest('id')
            ->first();

        if (! $request) {
            return null;
        }

        return route('clinic.verification-requests.pdf.preview', $request);
    }

    public function getPdfPresetOptions(): array
    {
        $clinic = $this->resolveClinic();

        $options = $clinic ? app(PdfPresetService::class)->optionsForClinic($clinic) : [];

        if (blank($this->data['verification_pdf_preset_id'] ?? null)) {
            return ['' => 'New unsaved preset'] + $options;
        }

        return $options;
    }

    public function getPdfOutputModeOptions(): array
    {
        return VerificationResultPdf::OUTPUT_MODE_OPTIONS;
    }

    public function getPdfSectionOptions(): array
    {
        return $this->getPdfSectionLabels();
    }

    public function getSummaryRows(): array
    {
        $clinic = $this->resolveClinic();
        $mode = VerificationResultPdf::normalizeOutputMode($this->data['verification_pdf_output_mode'] ?? 'standard');
        $presetName = $this->data['verification_pdf_preset_name'] ?? 'Full Verification Report';
        $sectionCount = VerificationResultPdf::isCustomOutputMode($mode)
            ? count((array) ($this->data['verification_pdf_output_sections'] ?? []))
            : count($this->getPdfSectionLabels());

        return [
            'Format' => 'PDF',
            'Preset' => $presetName,
            'Sections' => $sectionCount . ' included',
            'Orientation' => $mode === 'custom_landscape' ? 'Landscape' : 'Portrait',
            'Last Saved' => optional($clinic?->updated_at)->format('M d, Y h:i A') ?: '-',
            'Saved By' => auth()->user()?->name ?? 'Current user',
        ];
    }

    public function getCurrentOutputLabel(): string
    {
        $clinic = $this->resolveClinic();
        $mode = $clinic?->getVerificationPdfOutputMode() ?? 'standard';

        return VerificationResultPdf::OUTPUT_MODE_OPTIONS[$mode] ?? 'Standard';
    }

    public function getSelectedSectionLabels(): array
    {
        $clinic = $this->resolveClinic();

        return collect($clinic?->getVerificationPdfOutputSections() ?? [])
            ->map(fn (string $key): string => $this->getPdfSectionLabel($key))
            ->all();
    }

    public function getSelectedQuestionLabels(): array
    {
        $clinic = $this->resolveClinic();
        $questionIds = $clinic?->getVerificationPdfOutputQuestionIds() ?? [];

        if (empty($questionIds)) {
            return [];
        }

        return VerificationFormQuestion::query()
            ->whereIn('id', $questionIds)
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (VerificationFormQuestion $question): string => $question->prompt)
            ->all();
    }

    public function getSelectedQuestionSections(): Collection
    {
        $questionIds = is_array($this->data['verification_pdf_output_question_ids'] ?? null)
            ? $this->data['verification_pdf_output_question_ids']
            : ($this->resolveClinic()?->getVerificationPdfOutputQuestionIds() ?? []);

        if (empty($questionIds)) {
            return collect();
        }

        return VerificationFormQuestion::query()
            ->whereIn('id', $questionIds)
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key')
            ->map(function (Collection $questions, string $sectionKey): array {
                return [
                    'title' => $this->getPdfSectionLabel($sectionKey),
                    'questions' => $questions->pluck('prompt')->all(),
                ];
            });
    }

    public function selectAllQuestionsForSection(string $sectionKey): void
    {
        $options = $this->getQuestionOptionsForSection($sectionKey);

        $this->data['verification_pdf_output_question_ids_by_section'][$sectionKey] = array_map(
            static fn ($questionId): int => (int) $questionId,
            array_keys($options),
        );

        $this->syncFlattenedQuestionIds();
    }

    public function clearQuestionsForSection(string $sectionKey): void
    {
        $this->data['verification_pdf_output_question_ids_by_section'][$sectionKey] = [];

        $this->syncFlattenedQuestionIds();
    }

    public function updatedDataVerificationPdfOutputQuestionIdsBySection(): void
    {
        $this->syncFlattenedQuestionIds();
    }

    public function updatedDataVerificationPdfOutputMode(?string $mode): void
    {
        $normalizedMode = VerificationResultPdf::normalizeOutputMode($mode);
        $this->data['verification_pdf_output_mode'] = $normalizedMode;
        $this->data['verification_pdf_show_blank_rows'] = ! VerificationResultPdf::isCustomOutputMode($normalizedMode);
    }

    public function updatedDataVerificationPdfOutputSections(): void
    {
        $sections = is_array($this->data['verification_pdf_output_sections'] ?? null)
            ? $this->data['verification_pdf_output_sections']
            : [];
        $groupedQuestionIds = is_array($this->data['verification_pdf_output_question_ids_by_section'] ?? null)
            ? $this->data['verification_pdf_output_question_ids_by_section']
            : [];

        $this->data['verification_pdf_output_question_ids_by_section'] = $this->normalizeGroupedQuestionIds($sections, $groupedQuestionIds);
        $this->syncFlattenedQuestionIds();
    }

    public function getAvailableQuestionSectionsForSelection(): Collection
    {
        $clinic = $this->resolveClinic();
        $mode = $this->data['verification_pdf_output_mode'] ?? 'standard';
        $sectionKeys = is_array($this->data['verification_pdf_output_sections'] ?? null)
            ? $this->data['verification_pdf_output_sections']
            : [];

        if (! $clinic || ! VerificationResultPdf::isCustomOutputMode($mode) || empty($sectionKeys)) {
            return collect();
        }

        return VerificationFormQuestion::query()
            ->where('clinic_id', $clinic->getKey())
            ->where('is_active', true)
            ->whereIn('section_key', $sectionKeys)
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key')
            ->map(function (Collection $questions, string $sectionKey): array {
                $selectedIds = collect($this->data['verification_pdf_output_question_ids_by_section'][$sectionKey] ?? [])
                    ->map(fn ($questionId): int => (int) $questionId)
                    ->all();

                return [
                    'key' => $sectionKey,
                    'title' => $this->getPdfSectionLabel($sectionKey),
                    'count' => $questions->count(),
                    'selected_count' => count($selectedIds),
                    'questions' => $questions->map(fn (VerificationFormQuestion $question): array => [
                        'id' => (int) $question->getKey(),
                        'prompt' => $question->prompt,
                        'selected' => in_array((int) $question->getKey(), $selectedIds, true),
                    ])->all(),
                ];
            });
    }

    public function getQuestionSections(): Collection
    {
        $clinic = $this->resolveClinic();

        if (! $clinic) {
            return collect();
        }

        return VerificationFormQuestion::query()
            ->where('clinic_id', $clinic->getKey())
            ->where('is_active', true)
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key')
            ->map(function (Collection $questions, string $sectionKey): array {
                return [
                    'title' => $this->getPdfSectionLabel($sectionKey),
                    'count' => $questions->count(),
                    'questions' => $questions->pluck('prompt')->all(),
                ];
            });
    }

    protected function getPdfSectionLabels(): array
    {
        return collect(VerificationFormQuestion::templateThreeLiveSectionOptions())
            ->mapWithKeys(fn (string $label, string $key): array => [
                $key => $this->getPdfSectionLabel($key),
            ])
            ->all();
    }

    protected function getPdfSectionLabel(string $sectionKey): string
    {
        return self::PDF_SECTION_LABELS[$sectionKey]
            ?? VerificationFormQuestion::sectionLabel($sectionKey, VerificationFormQuestion::DEFAULT_TEMPLATE_KEY);
    }

    protected function getQuestionOptionsForSection(string $sectionKey): array
    {
        $clinic = $this->resolveClinic();

        if (! $clinic) {
            return [];
        }

        return VerificationFormQuestion::query()
            ->where('clinic_id', $clinic->getKey())
            ->where('is_active', true)
            ->where('section_key', $sectionKey)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (VerificationFormQuestion $question): array => [
                $question->getKey() => $question->prompt,
            ])
            ->all();
    }

    protected function groupQuestionIdsBySection(array $questionIds): array
    {
        if (empty($questionIds)) {
            return [];
        }

        return VerificationFormQuestion::query()
            ->whereIn('id', array_map('intval', $questionIds))
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key')
            ->map(fn (Collection $questions): array => $questions
                ->pluck('id')
                ->map(fn ($questionId): int => (int) $questionId)
                ->all()
            )
            ->all();
    }

    protected function normalizeGroupedQuestionIds(array $sectionKeys, array $groupedQuestionIds): array
    {
        $clinic = $this->resolveClinic();

        if (! $clinic || empty($sectionKeys)) {
            return [];
        }

        $normalized = [];

        foreach ($sectionKeys as $sectionKey) {
            $selectedIds = is_array($groupedQuestionIds[$sectionKey] ?? null)
                ? $groupedQuestionIds[$sectionKey]
                : [];

            $normalized[$sectionKey] = VerificationFormQuestion::query()
                ->where('clinic_id', $clinic->getKey())
                ->where('is_active', true)
                ->where('section_key', $sectionKey)
                ->whereIn('id', array_map('intval', $selectedIds))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($questionId): int => (int) $questionId)
                ->all();
        }

        return $normalized;
    }

    protected function flattenGroupedQuestionIds(array $groupedQuestionIds): array
    {
        return collect($groupedQuestionIds)
            ->flatten()
            ->map(fn ($questionId): int => (int) $questionId)
            ->unique()
            ->values()
            ->all();
    }

    protected function syncFlattenedQuestionIds(): void
    {
        $groupedQuestionIds = is_array($this->data['verification_pdf_output_question_ids_by_section'] ?? null)
            ? $this->data['verification_pdf_output_question_ids_by_section']
            : [];

        $this->data['verification_pdf_output_question_ids'] = $this->flattenGroupedQuestionIds($groupedQuestionIds);
    }

    protected function normalizeSelectedQuestionIds(array $sectionKeys, array $questionIds): array
    {
        $clinic = $this->resolveClinic();

        if (! $clinic || empty($sectionKeys) || empty($questionIds)) {
            return [];
        }

        return VerificationFormQuestion::query()
            ->where('clinic_id', $clinic->getKey())
            ->where('is_active', true)
            ->whereIn('section_key', $sectionKeys)
            ->whereIn('id', array_map('intval', $questionIds))
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($questionId): int => (int) $questionId)
            ->all();
    }

    protected function resolveClinic(): ?Clinic
    {
        if ($this->clinicRecord instanceof Clinic) {
            $selectedId = ClinicPanelScope::selectedClinicId();

            if ($selectedId && $this->clinicRecord->getKey() !== $selectedId) {
                $this->clinicRecord = null;
            }
        }

        if ($this->clinicRecord instanceof Clinic) {
            return $this->clinicRecord;
        }

        $selected = ClinicPanelScope::selectedClinic();

        if ($selected) {
            $this->clinicRecord = $selected;

            return $this->clinicRecord;
        }

        $user = auth()->user();

        if (! filled($user?->clinic_id)) {
            return null;
        }

        $this->clinicRecord = Clinic::query()
            ->with('organization')
            ->find($user->clinic_id);

        return $this->clinicRecord;
    }
}
