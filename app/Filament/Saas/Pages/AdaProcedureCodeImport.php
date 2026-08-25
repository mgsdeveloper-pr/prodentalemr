<?php

namespace App\Filament\Saas\Pages;

use App\Models\AdaProcedureCode;
use App\Models\SaasEntitlementAuditLog;
use App\Support\AdaProcedureCodeGovernance;
use App\Support\AdaProcedureCodeImportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AdaProcedureCodeImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'ADA/CDT Codes';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'ADA/CDT Codes';

    protected static ?string $slug = 'ada-cdt-codes';

    protected string $view = 'filament.saas.pages.ada-procedure-code-import';

    protected Width|string|null $maxContentWidth = Width::Full;

    public ?array $data = [];

    public ?array $previewResult = null;

    public ?array $lastImportResult = null;

    public bool $showImportPanel = false;

    public string $codeSearch = '';

    public string $lifecycleFilter = AdaProcedureCode::LIFECYCLE_ACTIVE;

    public ?int $selectedAuditCodeId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessSaasModule('settings') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getHeading(): string
    {
        return 'ADA/CDT Codes';
    }

    public function getSubheading(): ?string
    {
        return 'Maintain the central ADA/CDT code library used by verification templates.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Upload ADA/CDT file')
                    ->description('Import official ADA/CDT additions from an approved file. Single-code edits and deletes are locked; existing codes are skipped automatically unless a formal ADA revision workflow is introduced.')
                    ->schema([
                        FileUpload::make('import_file')
                            ->label('Drop ADA/CDT file here')
                            ->disk('local')
                            ->directory('imports/ada-cdt')
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                '.xlsx',
                                '.csv',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                                'text/plain',
                            ])
                            ->required()
                            ->helperText('Accepted headers: Code, Description. Optional: Class.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('showImport')
                ->label('Import Codes')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->action(function (): void {
                    $this->showImportPanel = true;
                }),
            Action::make('addCode')
                ->label('Add code')
                ->icon('heroicon-o-plus')
                ->form($this->codeGovernanceForm(includeCode: true))
                ->action(fn (array $data): null => $this->createGovernedCode($data)),
            Action::make('updateCode')
                ->label('Update code')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->form([
                    $this->codeSelectField(),
                    ...$this->codeGovernanceForm(includeCode: false),
                ])
                ->action(fn (array $data): null => $this->updateGovernedCode($data)),
            Action::make('removeByAda')
                ->label('Removed by ADA')
                ->icon('heroicon-o-archive-box-x-mark')
                ->color('danger')
                ->outlined()
                ->form([
                    $this->codeSelectField(activeOnly: true),
                    Textarea::make('retirement_reason')
                        ->label('Removal reason')
                        ->required()
                        ->rows(3)
                        ->helperText('Example: ADA removed this code from the current CDT publication.'),
                    TextInput::make('source_year')
                        ->label('Source year')
                        ->numeric()
                        ->default((int) date('Y'))
                        ->required(),
                    TextInput::make('source_document')
                        ->label('Source document/reference')
                        ->required()
                        ->maxLength(255),
                    DatePicker::make('effective_date')
                        ->label('Effective date'),
                    Textarea::make('governance_notes')
                        ->label('Internal notes')
                        ->rows(3),
                ])
                ->action(fn (array $data): null => $this->retireGovernedCode($data)),
        ];
    }

    public function closeImportPanel(): void
    {
        $this->showImportPanel = false;
        $this->previewResult = null;
        $this->lastImportResult = null;
        $this->form->fill();
    }

    public function previewCodes(AdaProcedureCodeImportService $importService): void
    {
        $uploadedFile = $this->resolveUploadedFile($this->data['import_file'] ?? null);
        $originalName = $uploadedFile?->getClientOriginalName();
        $storedPath = null;

        if (! $uploadedFile instanceof TemporaryUploadedFile) {
            Notification::make()
                ->title('Import file is required')
                ->danger()
                ->send();

            return;
        }

        try {
            $storedPath = $this->storeUploadedFile($uploadedFile, $originalName);
            $this->previewResult = $importService->previewFromStoredFile('local', $storedPath, $originalName);
            $this->lastImportResult = null;

            Notification::make()
                ->title('Preview ready')
                ->body(($this->previewResult['ready'] ?? 0) . ' ready, ' . ($this->previewResult['skipped'] ?? 0) . ' duplicate, ' . ($this->previewResult['failed'] ?? 0) . ' invalid.')
                ->color(($this->previewResult['failed'] ?? 0) > 0 ? 'warning' : 'success')
                ->send();
        } catch (\Throwable $throwable) {
            $this->previewResult = null;

            Notification::make()
                ->title('Preview failed')
                ->body($throwable->getMessage())
                ->danger()
                ->send();
        } finally {
            if (is_string($storedPath) && Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }
        }
    }

    public function importCodes(AdaProcedureCodeImportService $importService): void
    {
        $uploadedFile = $this->resolveUploadedFile($this->data['import_file'] ?? null);
        $originalName = $uploadedFile?->getClientOriginalName();
        $storedPath = null;

        if (! $uploadedFile instanceof TemporaryUploadedFile) {
            Notification::make()
                ->title('Import file is required')
                ->danger()
                ->send();

            return;
        }

        try {
            $storedPath = $this->storeUploadedFile($uploadedFile, $originalName);
            $result = $importService->importFromStoredFile('local', $storedPath, $originalName);
        } catch (\Throwable $throwable) {
            if (is_string($storedPath) && Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }

            $this->form->fill();

            Notification::make()
                ->title('Import failed')
                ->body($throwable->getMessage())
                ->danger()
                ->send();

            return;
        }

        if (is_string($storedPath) && Storage::disk('local')->exists($storedPath)) {
            Storage::disk('local')->delete($storedPath);
        }

        $this->form->fill();
        $this->lastImportResult = $result;
        $this->previewResult = null;

        Notification::make()
            ->title('ADA/CDT import completed')
            ->body(($result['imported'] ?? 0) . ' imported, ' . ($result['skipped'] ?? 0) . ' duplicate skipped, ' . ($result['failed'] ?? 0) . ' invalid.')
            ->color(($result['failed'] ?? 0) > 0 ? 'warning' : 'success')
            ->send();
    }

    public function createGovernedCode(array $data): null
    {
        app(AdaProcedureCodeGovernance::class)->create(auth()->user(), $data);

        Notification::make()
            ->title('ADA/CDT code added')
            ->body('The code was added with notes and audit history.')
            ->success()
            ->send();

        return null;
    }

    public function updateGovernedCode(array $data): null
    {
        $code = AdaProcedureCode::query()->findOrFail($data['code_id']);

        app(AdaProcedureCodeGovernance::class)->update(auth()->user(), $code, $data);

        Notification::make()
            ->title('ADA/CDT code updated')
            ->body('The code was updated with before/after audit history.')
            ->success()
            ->send();

        return null;
    }

    public function retireGovernedCode(array $data): null
    {
        $code = AdaProcedureCode::query()->findOrFail($data['code_id']);

        app(AdaProcedureCodeGovernance::class)->retireByAda(auth()->user(), $code, $data);

        Notification::make()
            ->title('Code marked removed by ADA')
            ->body('The code remains available for historical records but is hidden from active user pickers.')
            ->warning()
            ->send();

        return null;
    }

    public function getTotalCodeCount(): int
    {
        return AdaProcedureCode::query()->count();
    }

    public function getActiveCodeCount(): int
    {
        return AdaProcedureCode::query()->active()->count();
    }

    public function getLatestCodes()
    {
        return AdaProcedureCode::query()
            ->latest('id')
            ->limit(8)
            ->get();
    }

    public function getLifecycleCounts(): array
    {
        return [
            'all' => AdaProcedureCode::query()->count(),
            AdaProcedureCode::LIFECYCLE_ACTIVE => AdaProcedureCode::query()->active()->count(),
            AdaProcedureCode::LIFECYCLE_INACTIVE => AdaProcedureCode::query()->where('lifecycle_status', AdaProcedureCode::LIFECYCLE_INACTIVE)->count(),
            AdaProcedureCode::LIFECYCLE_DEPRECATED => AdaProcedureCode::query()->where('lifecycle_status', AdaProcedureCode::LIFECYCLE_DEPRECATED)->count(),
            AdaProcedureCode::LIFECYCLE_REMOVED_BY_ADA => AdaProcedureCode::query()->where('lifecycle_status', AdaProcedureCode::LIFECYCLE_REMOVED_BY_ADA)->count(),
        ];
    }

    public function getManagedCodes(): Collection
    {
        return AdaProcedureCode::query()
            ->when($this->lifecycleFilter === AdaProcedureCode::LIFECYCLE_ACTIVE, fn ($query) => $query->active())
            ->when($this->lifecycleFilter !== 'all' && $this->lifecycleFilter !== AdaProcedureCode::LIFECYCLE_ACTIVE, fn ($query) => $query->where('lifecycle_status', $this->lifecycleFilter))
            ->when(filled($this->codeSearch), function ($query): void {
                $search = trim($this->codeSearch);

                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('procedure_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('class', 'like', "%{$search}%")
                        ->orWhere('source_document', 'like', "%{$search}%");
                });
            })
            ->orderBy('procedure_code')
            ->limit(75)
            ->get();
    }

    public function selectAuditCode(int $codeId): void
    {
        $this->selectedAuditCodeId = $codeId;
    }

    public function clearAuditCode(): void
    {
        $this->selectedAuditCodeId = null;
    }

    public function getSelectedAuditCode(): ?AdaProcedureCode
    {
        return $this->selectedAuditCodeId
            ? AdaProcedureCode::query()->find($this->selectedAuditCodeId)
            : null;
    }

    public function getSelectedCodeAuditEntries(): Collection
    {
        if (! $this->selectedAuditCodeId) {
            return collect();
        }

        return SaasEntitlementAuditLog::query()
            ->with('actorUser')
            ->where('entity_type', AdaProcedureCode::class)
            ->where('entity_id', $this->selectedAuditCodeId)
            ->whereIn('event_type', ['ada_code_created', 'ada_code_updated', 'ada_code_removed_by_ada'])
            ->latest('id')
            ->limit(10)
            ->get();
    }

    protected function codeGovernanceForm(bool $includeCode): array
    {
        return [
            TextInput::make('procedure_code')
                ->label('ADA/CDT code')
                ->visible($includeCode)
                ->required($includeCode)
                ->maxLength(10)
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null),
            TextInput::make('description')
                ->label('Description')
                ->required()
                ->maxLength(65535),
            TextInput::make('class')
                ->label('Class/category')
                ->maxLength(150),
            TextInput::make('source_year')
                ->label('Source year')
                ->numeric()
                ->default((int) date('Y'))
                ->required(),
            TextInput::make('source_document')
                ->label('Source document/reference')
                ->required()
                ->maxLength(255),
            TextInput::make('source_page')
                ->label('Source page')
                ->numeric(),
            DatePicker::make('effective_date')
                ->label('Effective date'),
            Textarea::make('governance_notes')
                ->label('Reason / notes')
                ->required()
                ->rows(3),
        ];
    }

    protected function codeSelectField(bool $activeOnly = false): Select
    {
        return Select::make('code_id')
            ->label('ADA/CDT code')
            ->searchable()
            ->required()
            ->options(fn (): array => $this->codeOptions('', $activeOnly))
            ->getSearchResultsUsing(fn (string $search): array => $this->codeOptions($search, $activeOnly))
            ->getOptionLabelUsing(fn ($value): ?string => $value ? $this->codeLabel((int) $value) : null);
    }

    protected function codeOptions(string $search = '', bool $activeOnly = false): array
    {
        return AdaProcedureCode::query()
            ->when($activeOnly, fn ($query) => $query->active())
            ->when(filled($search), function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('procedure_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('procedure_code')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (AdaProcedureCode $code): array => [$code->getKey() => $this->formatCodeLabel($code)])
            ->all();
    }

    protected function codeLabel(int $id): ?string
    {
        $code = AdaProcedureCode::query()->find($id);

        return $code ? $this->formatCodeLabel($code) : null;
    }

    protected function formatCodeLabel(AdaProcedureCode $code): string
    {
        return trim($code->procedure_code . ' - ' . str($code->description)->limit(80));
    }

    protected function storeUploadedFile(TemporaryUploadedFile $uploadedFile, ?string $originalName): string
    {
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: pathinfo($originalName ?? '', PATHINFO_EXTENSION) ?: 'csv');
        $directory = 'imports/ada-cdt';
        $filename = Str::uuid()->toString() . '.' . $extension;
        $storedPath = $directory . '/' . $filename;
        $disk = Storage::disk('local');

        $disk->makeDirectory($directory);

        $sourcePath = $uploadedFile->getRealPath() ?: $uploadedFile->getPathname();

        if (! is_string($sourcePath) || $sourcePath === '' || ! is_file($sourcePath)) {
            throw new \RuntimeException('Uploaded file could not be located for import.');
        }

        $contents = file_get_contents($sourcePath);

        if ($contents === false) {
            throw new \RuntimeException('Uploaded file could not be opened for import.');
        }

        $written = $disk->put($storedPath, $contents);

        if (! $written || ! $disk->exists($storedPath)) {
            throw new \RuntimeException('Upload could not be stored for import.');
        }

        return $storedPath;
    }

    protected function resolveUploadedFile(mixed $state): ?TemporaryUploadedFile
    {
        if ($state instanceof TemporaryUploadedFile) {
            return $state;
        }

        if (is_array($state)) {
            foreach ($state as $item) {
                if ($item instanceof TemporaryUploadedFile) {
                    return $item;
                }
            }
        }

        return null;
    }
}
