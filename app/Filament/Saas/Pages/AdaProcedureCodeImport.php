<?php

namespace App\Filament\Saas\Pages;

use App\Models\AdaProcedureCode;
use App\Support\AdaProcedureCodeImportService;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AdaProcedureCodeImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'ADA/CDT Codes';

    protected static ?int $navigationSort = 55;

    protected static ?string $title = 'ADA/CDT Code Import';

    protected static ?string $slug = 'ada-cdt-codes';

    protected string $view = 'filament.saas.pages.ada-procedure-code-import';

    protected Width|string|null $maxContentWidth = Width::Full;

    public ?array $data = [];

    public ?array $previewResult = null;

    public ?array $lastImportResult = null;

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
        return '';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Upload ADA/CDT file')
                    ->description('Import a CSV or Excel file with only the important columns: Code and Description. Existing codes are skipped automatically.')
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
        return [];
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

    public function getTotalCodeCount(): int
    {
        return AdaProcedureCode::query()->count();
    }

    public function getActiveCodeCount(): int
    {
        return AdaProcedureCode::query()->where('is_active', true)->count();
    }

    public function getLatestCodes()
    {
        return AdaProcedureCode::query()
            ->latest('id')
            ->limit(8)
            ->get();
    }

    protected function storeUploadedFile(TemporaryUploadedFile $uploadedFile, ?string $originalName): string
    {
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: pathinfo($originalName ?? '', PATHINFO_EXTENSION) ?: 'csv');
        $directory = 'imports/ada-cdt';
        $filename = Str::uuid()->toString() . '.' . $extension;
        $storedPath = $directory . '/' . $filename;
        $disk = Storage::disk('local');

        $disk->makeDirectory($directory);

        $stream = fopen($uploadedFile->getRealPath(), 'rb');

        if ($stream === false) {
            throw new \RuntimeException('Uploaded file could not be opened for import.');
        }

        $written = $disk->put($storedPath, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

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
