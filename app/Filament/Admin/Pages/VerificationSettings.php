<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Models\Clinic;
use App\Models\PortalCredential;
use App\Models\VerificationFormQuestion;
use App\Support\AdminClinicScope;
use App\Support\VerificationResultPdf;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
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

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'Verification Settings';

    protected static ?string $slug = 'verification-settings';

    protected string $view = 'filament.admin.pages.verification-settings';

    public ?array $data = [];

    protected ?Clinic $clinicRecord = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageVerificationSettings() ?? false;
    }

    public function mount(): void
    {
        $this->clinicRecord = $this->resolveClinic();
        $selectedQuestionIds = $this->clinicRecord?->getVerificationPdfOutputQuestionIds() ?? [];

        $this->form->fill([
            'verification_default_form_template' => $this->clinicRecord?->getVerificationDefaultFormTemplate() ?? VerificationFormQuestion::defaultTemplateKey(),
            'verification_pdf_output_mode' => $this->clinicRecord?->getVerificationPdfOutputMode() ?? 'standard',
            'verification_pdf_output_sections' => $this->clinicRecord?->getVerificationPdfOutputSections() ?? [],
            'verification_pdf_output_question_ids' => $selectedQuestionIds,
            'verification_pdf_output_question_ids_by_section' => $this->groupQuestionIdsBySection($selectedQuestionIds),
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
                        Select::make('verification_pdf_output_mode')
                            ->label('Default PDF output')
                            ->options(VerificationResultPdf::OUTPUT_MODE_OPTIONS)
                            ->default('standard')
                            ->required()
                            ->native(false)
                            ->live(),
                        Select::make('verification_default_form_template')
                            ->label('Default verification form')
                            ->options(VerificationFormQuestion::ACTIVE_TEMPLATE_OPTIONS)
                            ->default(VerificationFormQuestion::defaultTemplateKey())
                            ->required()
                            ->native(false)
                            ->helperText('Verification Workbench is the recommended default and the active verification form experience.'),
                        CheckboxList::make('verification_pdf_output_sections')
                            ->label('Selected output sections')
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
                            ->visible(fn (Get $get): bool => $get('verification_pdf_output_mode') === 'selected')
                            ->helperText('Select one or more sections first. Then use the section cards in "Choose Questions to Include" below to pick exact questions.'),
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
            Action::make('save')
                ->label('Save settings')
                ->action('save'),
        ];
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

        $groupedQuestionIds = $mode === 'selected'
            ? $this->normalizeGroupedQuestionIds($sections, $groupedQuestionIds)
            : [];
        $questionIds = $this->flattenGroupedQuestionIds($groupedQuestionIds);

        if ($mode === 'selected' && empty($sections)) {
            Notification::make()
                ->title('Select at least one section')
                ->body('Choose the verification sections that should appear in the selected output PDF.')
                ->danger()
                ->send();

            return;
        }

        $clinic->update([
            'verification_default_form_template' => $state['verification_default_form_template'] ?? VerificationFormQuestion::defaultTemplateKey(),
            'verification_pdf_output_mode' => $mode,
            'verification_pdf_output_sections' => $mode === 'selected' ? array_values($sections) : [],
            'verification_pdf_output_question_ids' => $mode === 'selected' ? array_values($questionIds) : [],
        ]);

        $this->clinicRecord = $clinic->fresh('organization');
        $selectedQuestionIds = $this->clinicRecord->getVerificationPdfOutputQuestionIds();
        $this->form->fill([
            'verification_default_form_template' => $this->clinicRecord->getVerificationDefaultFormTemplate(),
            'verification_pdf_output_mode' => $this->clinicRecord->getVerificationPdfOutputMode(),
            'verification_pdf_output_sections' => $this->clinicRecord->getVerificationPdfOutputSections(),
            'verification_pdf_output_question_ids' => $selectedQuestionIds,
            'verification_pdf_output_question_ids_by_section' => $this->groupQuestionIdsBySection($selectedQuestionIds),
        ]);

        Notification::make()
            ->title('Verification settings saved')
            ->body('The clinic PDF output template has been updated successfully.')
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

        if (! $clinic || $mode !== 'selected' || empty($sectionKeys)) {
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
        return collect(VerificationFormQuestion::SECTION_OPTIONS)
            ->mapWithKeys(fn (string $label, string $key): array => [
                $key => $this->getPdfSectionLabel($key),
            ])
            ->all();
    }

    protected function getPdfSectionLabel(string $sectionKey): string
    {
        return self::PDF_SECTION_LABELS[$sectionKey]
            ?? VerificationFormQuestion::SECTION_OPTIONS[$sectionKey]
            ?? str($sectionKey)->headline()->toString();
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
