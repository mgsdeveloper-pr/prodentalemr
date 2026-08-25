<?php

namespace App\Filament\Saas\Resources\InsuranceCarriers\Pages;

use App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource;
use App\Support\InsuranceCarrierImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportInsuranceCarriers extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = InsuranceCarrierResource::class;

    protected string $view = 'filament.saas.resources.insurance-carriers.pages.import-insurance-carriers';

    protected Width|string|null $maxContentWidth = Width::Full;

    public ?array $data = [];

    public ?array $previewResult = null;

    public function mount(): void
    {
        abort_unless(InsuranceCarrierResource::canCreate(), 403);
        $this->form->fill();
    }

    public function getTitle(): string
    {
        return 'Import Insurance Directory';
    }

    public function getSubheading(): ?string
    {
        return 'Preview and import central payer information without creating duplicate insurance records.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Insurance file')
                    ->description('Upload a CSV or XLSX file using the supported central-directory columns.')
                    ->schema([
                        FileUpload::make('import_file')
                            ->label('Insurance directory file')
                            ->disk('local')
                            ->directory('imports/insurance-directory')
                            ->acceptedFileTypes([
                                '.xlsx',
                                '.csv',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                                'text/plain',
                            ])
                            ->required()
                            ->helperText('Required: insurance_name. Optional: payer_id, payer_phone, claims_address, website, notes, is_active.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadSample')
                ->label('Download sample')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(url('/samples/insurance-directory-import-sample.csv')),
            Action::make('back')
                ->label('Back to Insurance')
                ->color('gray')
                ->url(InsuranceCarrierResource::getUrl('index')),
        ];
    }

    public function previewImport(InsuranceCarrierImportService $service): void
    {
        $this->processUpload($service, false);
    }

    public function importInsurance(InsuranceCarrierImportService $service): void
    {
        $this->processUpload($service, true);
    }

    protected function processUpload(InsuranceCarrierImportService $service, bool $persist): void
    {
        $uploadedFile = $this->resolveUploadedFile($this->data['import_file'] ?? null);

        if (! $uploadedFile) {
            Notification::make()->title('Import file is required')->danger()->send();

            return;
        }

        $storedPath = null;

        try {
            $originalName = $uploadedFile->getClientOriginalName();
            $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: 'csv');
            $storedPath = $uploadedFile->storeAs(
                'imports/insurance-directory',
                Str::uuid()->toString().'.'.$extension,
                'local',
            );

            if (! is_string($storedPath) || $storedPath === '') {
                throw new \RuntimeException('The uploaded file could not be stored.');
            }

            $absolutePath = Storage::disk('local')->path($storedPath);
            $result = $persist
                ? $service->import($absolutePath, $originalName)
                : $service->preview($absolutePath, $originalName);

            $this->previewResult = $result;

            Notification::make()
                ->title($persist ? 'Insurance import completed' : 'Import preview ready')
                ->body($this->summary($result))
                ->color(($result['failed'] ?? 0) > 0 ? 'warning' : 'success')
                ->send();

            if ($persist) {
                $this->form->fill();
            }
        } catch (\Throwable $throwable) {
            Log::warning('Insurance directory import failed.', [
                'user_id' => auth()->id(),
                'message' => $throwable->getMessage(),
            ]);

            Notification::make()
                ->title('Insurance import failed')
                ->body($throwable->getMessage())
                ->danger()
                ->send();
        } finally {
            if (is_string($storedPath) && Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }
        }
    }

    protected function summary(array $result): string
    {
        return collect([
            ($result['total'] ?? 0).' row(s)',
            ($result['created'] ?? 0).' new',
            ($result['updated'] ?? 0).' updated',
            ($result['unchanged'] ?? 0).' unchanged',
            ($result['failed'] ?? 0) > 0 ? ($result['failed'] ?? 0).' failed' : null,
        ])->filter()->implode(' | ');
    }

    protected function resolveUploadedFile(mixed $state): ?TemporaryUploadedFile
    {
        if ($state instanceof TemporaryUploadedFile) {
            return $state;
        }

        if (is_array($state)) {
            return collect($state)->first(fn ($file): bool => $file instanceof TemporaryUploadedFile);
        }

        return null;
    }
}
