<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\Clinic;
use App\Models\PortalCredential;
use App\Models\VerificationFormQuestion;
use App\Services\Verification\PdfPresetService;
use App\Support\AdminClinicScope;
use App\Support\VerificationResultPdf;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use UnitEnum;

class VerificationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected const PDF_SECTION_LABELS = [
        'core_details' => 'Core Eligibility',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'PDF & Output';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'PDF & Output';

    protected static ?string $slug = 'verification-settings';

    protected string $view = 'filament.admin.pages.verification-settings';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public ?array $data = [];

    protected ?Clinic $clinicRecord = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageVerificationSettings() ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Choose the clinic PDF preset, layout, sections, questions, and blank-row behavior.';
    }

    public function getBreadcrumbs(): array
    {
        return [
            VerificationRequestResource::getUrl('index') => 'Verification',
            VerificationGeneralSettings::getUrl() => 'Settings',
            'PDF & Output',
        ];
    }

    public function mount(): void
    {
        $this->clinicRecord = $this->resolveClinic();
        if ($this->clinicRecord) {
            app(PdfPresetService::class)->seedDefaultsForClinic($this->clinicRecord);
            $this->clinicRecord->refresh();
        }

        $defaultPreset = $this->clinicRecord ? app(PdfPresetService::class)->defaultForClinic($this->clinicRecord) : null;
        $selectedQuestionIds = $defaultPreset?->getQuestionIds() ?? $this->clinicRecord?->getVerificationPdfOutputQuestionIds() ?? [];

        $this->form->fill([
            'verification_pdf_preset_id' => $defaultPreset?->getKey(),
            'verification_pdf_preset_name' => $defaultPreset?->name ?? 'Full Verification Report',
            'verification_pdf_preset_description' => $defaultPreset?->description,
            'verification_pdf_preset_is_default' => true,
            'verification_pdf_output_mode' => $defaultPreset?->getOutputMode() ?? $this->clinicRecord?->getVerificationPdfOutputMode() ?? 'standard',
            'verification_pdf_output_sections' => $defaultPreset?->getSectionKeys() ?? $this->clinicRecord?->getVerificationPdfOutputSections() ?? [],
            'verification_pdf_output_question_ids' => $selectedQuestionIds,
            'verification_pdf_output_question_ids_by_section' => $this->groupQuestionIdsBySection($selectedQuestionIds),
            'verification_pdf_show_blank_rows' => $defaultPreset?->shouldShowBlankRows() ?? true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('PDF Output Template')
                    ->description('Choose the verification PDF format once for this clinic. Every Clinic and Admin user will use the same output for this clinic.')
                    ->schema([
                        Placeholder::make('selected_clinic')
                            ->label('Clinic scope')
                            ->content(function (): string {
                                $clinic = $this->resolveClinic();

                                return $clinic?->clinic_name
                                    ? $clinic->clinic_name . ' - ' . ($clinic->organization?->name ?? '')
                                    : 'Select a clinic from the Workspace menu before changing verification settings.';
                            }),
                        Select::make('verification_pdf_preset_id')
                            ->label('PDF preset profile')
                            ->options(fn (): array => ($clinic = $this->resolveClinic())
                                ? app(PdfPresetService::class)->optionsForClinic($clinic)
                                : [])
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (?int $state): mixed => $state ? $this->loadPreset($state) : null)
                            ->helperText('Choose a saved PDF profile, or use Create New Preset to start another profile.'),
                        TextInput::make('verification_pdf_preset_name')
                            ->label('Preset name')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('verification_pdf_preset_description')
                            ->label('Preset description')
                            ->rows(2)
                            ->maxLength(500),
                        Toggle::make('verification_pdf_preset_is_default')
                            ->label('Use as clinic default preset')
                            ->default(true)
                            ->inline(false),
                        Select::make('verification_pdf_output_mode')
                            ->label('PDF layout')
                            ->options(VerificationResultPdf::OUTPUT_MODE_OPTIONS)
                            ->default('standard')
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (?string $state, Set $set): mixed => $set(
                                'verification_pdf_show_blank_rows',
                                ! VerificationResultPdf::isCustomOutputMode($state)
                            ))
                            ->helperText('Standard prints the normal clinic report. Custom Portrait and Custom Landscape allow section and question selection.'),
                        Toggle::make('verification_pdf_show_blank_rows')
                            ->label('Show blank rows')
                            ->helperText('When off, unanswered questions are skipped. Leave off for compact one-page custom outputs.')
                            ->default(fn (Get $get): bool => ! VerificationResultPdf::isCustomOutputMode($get('verification_pdf_output_mode')))
                            ->inline(false),
                        CheckboxList::make('verification_pdf_output_sections')
                            ->label('Custom output sections')
                            ->options($this->getPdfSectionLabels())
                            ->columns(2)
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?array $state): void {
                                $normalizedSections = is_array($state) ? $state : [];
                                $groupedQuestionIds = is_array($this->data['verification_pdf_output_question_ids_by_section'] ?? null)
                                    ? $this->data['verification_pdf_output_question_ids_by_section']
                                    : [];

                                $normalizedGroupedState = $this->normalizeGroupedQuestionIds($normalizedSections, $groupedQuestionIds);

                                $set(
                                    'verification_pdf_output_question_ids',
                                    $this->flattenGroupedQuestionIds($normalizedGroupedState)
                                );
                                $set('verification_pdf_output_question_ids_by_section', $normalizedGroupedState);
                            })
                            ->visible(fn (Get $get): bool => VerificationResultPdf::isCustomOutputMode($get('verification_pdf_output_mode')))
                            ->helperText('Used only for Custom Portrait and Custom Landscape. Select sections first, then choose the exact questions below.'),
                    ])
                    ->columns(1),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageQuestions')
                ->label('Manage verification questions')
                ->icon('heroicon-o-rectangle-stack')
                ->url(fn (): string => VerificationFormQuestionResource::getUrl('index'))
                ->color('gray'),
            Action::make('newPreset')
                ->label('Create New Preset')
                ->icon('heroicon-o-document-plus')
                ->color('gray')
                ->action('createNewPreset'),
            Action::make('save')
                ->label('Save preset')
                ->action('save'),
        ];
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

    public function loadPreset(int $presetId): void
    {
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
                ->title('Select a clinic')
                ->body('Choose a clinic from the Workspace menu before saving PDF output settings.')
                ->danger()
                ->send();

            return;
        }

        $this->syncFlattenedQuestionIds();

        $state = array_merge($this->data, $this->form->getState());
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

        $clinic->update([
            'default_verification_pdf_preset_id' => ($state['verification_pdf_preset_is_default'] ?? true) ? $savedPreset->getKey() : $clinic->default_verification_pdf_preset_id,
        ]);

        $this->clinicRecord = $clinic->fresh('organization');
        $selectedQuestionIds = $savedPreset->getQuestionIds();
        $this->form->fill([
            'verification_pdf_preset_id' => $savedPreset->getKey(),
            'verification_pdf_preset_name' => $savedPreset->name,
            'verification_pdf_preset_description' => $savedPreset->description,
            'verification_pdf_preset_is_default' => $savedPreset->is_default,
            'verification_pdf_output_mode' => $savedPreset->getOutputMode(),
            'verification_pdf_output_sections' => $savedPreset->getSectionKeys(),
            'verification_pdf_output_question_ids' => $selectedQuestionIds,
            'verification_pdf_output_question_ids_by_section' => $this->groupQuestionIdsBySection($selectedQuestionIds),
            'verification_pdf_show_blank_rows' => $savedPreset->shouldShowBlankRows(),
        ]);

        Notification::make()
            ->title('PDF preset saved')
            ->body('The clinic PDF preset has been updated successfully.')
            ->success()
            ->send();
    }

    public function getSelectedClinic(): ?Clinic
    {
        return $this->resolveClinic();
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

    public function getPortalCredentials(): Collection
    {
        return PortalCredential::query()
            ->withCount('overrides')
            ->orderByDesc('is_active')
            ->orderBy('portal_name')
            ->get();
    }

    public function canManagePortalCredentials(): bool
    {
        return (bool) (
            auth()->user()?->canAccessVerificationModule('portal_credentials')
            || auth()->user()?->canAccessSaasModule('portal_credentials')
        );
    }

    public function canCreatePortalCredentials(): bool
    {
        return (bool) (
            auth()->user()?->canPerformVerificationModuleAction('portal_credentials', 'add')
            || auth()->user()?->canPerformSaasModuleAction('portal_credentials', 'add')
        );
    }

    public function canEditPortalCredentials(): bool
    {
        return (bool) (
            auth()->user()?->canPerformVerificationModuleAction('portal_credentials', 'update')
            || auth()->user()?->canPerformSaasModuleAction('portal_credentials', 'update')
        );
    }

    public function canDeletePortalCredentials(): bool
    {
        return (bool) (
            auth()->user()?->canPerformVerificationModuleAction('portal_credentials', 'delete')
            || auth()->user()?->canPerformSaasModuleAction('portal_credentials', 'delete')
        );
    }

    public function createPortalCredential(): RedirectResponse
    {
        return redirect()->to(\App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource::getUrl('create'));
    }

    public function editPortalCredential(int $credentialId): RedirectResponse
    {
        return redirect()->to(\App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource::getUrl('edit', ['record' => $credentialId]));
    }

    public function deletePortalCredential(int $credentialId): void
    {
        if (! $this->canDeletePortalCredentials()) {
            Notification::make()
                ->title('You do not have access')
                ->body('Your account cannot remove portal credentials.')
                ->danger()
                ->send();

            return;
        }

        PortalCredential::query()->findOrFail($credentialId)->delete();

        Notification::make()
            ->title('Portal credential removed')
            ->body('The portal credential has been removed from the shared verification vault.')
            ->success()
            ->send();
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

    protected function resolveClinic(): ?Clinic
    {
        if ($this->clinicRecord instanceof Clinic) {
            $selectedId = AdminClinicScope::selectedClinicId();

            if ($selectedId && $this->clinicRecord->getKey() !== $selectedId) {
                $this->clinicRecord = null;
            }
        }

        if ($this->clinicRecord instanceof Clinic) {
            return $this->clinicRecord;
        }

        $selected = AdminClinicScope::selectedClinic();

        if ($selected) {
            $this->clinicRecord = $selected;

            return $this->clinicRecord;
        }

        return null;
    }
}
