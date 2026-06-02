<?php

namespace App\Filament\Saas\Resources\Verifications\Pages;

use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use App\Support\AdminClinicScope;
use App\Support\VerificationRequestImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
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

class ImportVerificationWorkItems extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = VerificationWorkItemResource::class;

    protected string $view = 'filament.saas.resources.verifications.pages.import-verification-work-items';

    protected Width|string|null $maxContentWidth = Width::Full;

    public ?array $data = [];
    public ?array $lastImportResult = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getTitle(): string
    {
        return 'Import Verification Requests';
    }

    public function form(Schema $schema): Schema
    {
        $selectedClinic = AdminClinicScope::selectedClinic();

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Import Excel')
                    ->description('Upload a completed verification workbook and create Admin queue items in bulk for the selected clinic.')
                    ->schema([
                        Placeholder::make('selected_clinic')
                            ->label('Clinic scope')
                            ->content($selectedClinic?->clinic_name
                                ? $selectedClinic->clinic_name . ' - ' . ($selectedClinic->organization?->name ?? '')
                                : 'Select a clinic from the Workspace menu before importing verification requests.'),
                        FileUpload::make('import_file')
                            ->label('Verification request file')
                            ->disk('local')
                            ->directory('imports/verification-requests')
                            ->preserveFilenames()
                            ->storeFileNamesIn('import_file_file_names')
                            ->acceptedFileTypes([
                                '.xlsx',
                                '.csv',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                                'text/plain',
                            ])
                            ->required()
                            ->helperText('Upload the sample workbook or a CSV with the same headers. Admin import always creates service-side verification queue items.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadSample')
                ->label('Download sample Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(url('/samples/verification-request-import-sample.xlsx'))
                ->openUrlInNewTab(),
            Action::make('back')
                ->label('Back to queue')
                ->url(VerificationWorkItemResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function importRequests(VerificationRequestImportService $importService): void
    {
        $clinic = AdminClinicScope::selectedClinic();
        $user = auth()->user();
        $uploadedFile = $this->resolveUploadedFile($this->data['import_file'] ?? null);
        $originalName = $uploadedFile?->getClientOriginalName();
        $storedPath = null;

        if (! $clinic) {
            Notification::make()
                ->title('Select a clinic first')
                ->body('Choose a clinic from the Workspace menu before importing verification requests.')
                ->danger()
                ->send();

            return;
        }

        if (! $uploadedFile instanceof TemporaryUploadedFile) {
            Notification::make()
                ->title('Import file is required')
                ->danger()
                ->send();

            return;
        }

        try {
            $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: pathinfo($originalName ?? '', PATHINFO_EXTENSION) ?: 'xlsx');
            $storedPath = $uploadedFile->storeAs(
                'imports/verification-requests',
                Str::uuid()->toString() . '.' . $extension,
                'local',
            );

            if (! is_string($storedPath) || $storedPath === '') {
                throw new \RuntimeException('Upload could not be stored for import.');
            }

            $result = $importService->importForAdminFromStoredFile('local', $storedPath, $clinic, $user, $originalName);
        } catch (\Throwable $throwable) {
            Log::warning('Admin verification import failed.', [
                'clinic_id' => $clinic->id ?? null,
                'user_id' => $user?->id,
                'stored_path' => $storedPath,
                'original_name' => $originalName,
                'message' => $throwable->getMessage(),
            ]);

            if (is_string($storedPath) && Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }

            $this->form->fill();

            Notification::make()
                ->title('Import failed')
                ->body('We could not read that file. Please use the downloaded sample workbook or a plain CSV with the same headers.')
                ->danger()
                ->send();

            return;
        }

        if (is_string($storedPath) && Storage::disk('local')->exists($storedPath)) {
            Storage::disk('local')->delete($storedPath);
        }

        $this->form->fill();

        $summary = collect([
            ($result['total'] ?? 0) . ' total row(s)',
            $result['imported'] . ' request(s) imported',
            ($result['duplicates'] ?? 0) > 0 ? ($result['duplicates'] ?? 0) . ' duplicate(s) skipped' : null,
            $result['failed'] > 0 ? $result['failed'] . ' failed' : null,
            count($result['errors']) > 0 ? implode(' | ', array_slice($result['errors'], 0, 2)) : null,
        ])->filter()->implode(' | ');

        $this->lastImportResult = $result;

        Notification::make()
            ->title('Verification import completed')
            ->body($summary)
            ->color(($result['failed'] > 0 || ($result['duplicates'] ?? 0) > 0) ? 'warning' : 'success')
            ->send();
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
