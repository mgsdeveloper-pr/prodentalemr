<?php

namespace App\Filament\Admin\Resources\Appointments\Pages;

use App\Filament\Admin\Resources\Appointments\AppointmentResource;
use App\Models\AppointmentImportBatch;
use App\Support\AdminClinicScope;
use App\Support\AppointmentImportService;
use App\Support\SaasEntitlements;
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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportAppointments extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AppointmentResource::class;

    protected string $view = 'filament.admin.resources.appointments.pages.import-appointments';

    protected Width|string|null $maxContentWidth = Width::Full;

    public ?array $data = [];

    public ?array $previewResult = null;

    public ?array $lastImportResult = null;

    public static function canAccess(array $parameters = []): bool
    {
        return AppointmentResource::canCreate()
            && SaasEntitlements::userFeatureAllowed(auth()->user(), 'appointment_import', AdminClinicScope::selectedClinic());
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getTitle(): string
    {
        return 'Import Appointments';
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getBackUrl(): string
    {
        return AppointmentResource::getUrl('index');
    }

    public function getSelectedClinicScopeLabel(): string
    {
        $selectedClinic = AdminClinicScope::selectedClinic();

        return $selectedClinic?->clinic_name
            ? $selectedClinic->clinic_name . ' - ' . ($selectedClinic->organization?->name ?? '')
            : 'Select a clinic from the Clinic Scope menu before importing.';
    }

    public function getAcceptedColumns(): array
    {
        return [
            'patient_full_name',
            'first_name',
            'last_name',
            'patient_dob',
            'phone',
            'email',
            'pms_patient_id',
            'appointment_date',
            'appointment_time',
            'service',
            'provider_name',
            'location_name',
            'duration_minutes',
            'status',
            'notes',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Upload appointment file')
                    ->description('CSV or Excel is supported. Required columns: patient name, appointment_date, and service.')
                    ->schema([
                        FileUpload::make('import_file')
                            ->label('Drop CSV or Excel file here')
                            ->disk('local')
                            ->directory('imports/appointments')
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
                            ->helperText('Accepted: .csv, .xlsx, .xls. New patients are created automatically when no match is found.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function previewAppointments(AppointmentImportService $importService): void
    {
        $clinic = AdminClinicScope::selectedClinic();
        $uploadedFile = $this->resolveUploadedFile($this->data['import_file'] ?? null);
        $originalName = $uploadedFile?->getClientOriginalName();
        $storedPath = null;

        if (! $clinic) {
            Notification::make()
                ->title('Select a clinic first')
                ->body('Choose one clinic from Clinic Scope before previewing appointments.')
                ->danger()
                ->send();

            return;
        }

        if (! $uploadedFile instanceof TemporaryUploadedFile) {
            Notification::make()
                ->title('Appointment file is required')
                ->danger()
                ->send();

            return;
        }

        try {
            $storedPath = $this->storeUploadedFile($uploadedFile, $originalName);
            $this->previewResult = $importService->previewFromStoredFile('local', $storedPath, $clinic, $originalName);
            $this->lastImportResult = null;

            Notification::make()
                ->title('Preview ready')
                ->body(($this->previewResult['ready'] ?? 0) . ' ready, ' . ($this->previewResult['failed'] ?? 0) . ' need attention.')
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

    public function importAppointments(AppointmentImportService $importService): void
    {
        $clinic = AdminClinicScope::selectedClinic();
        $user = auth()->user();
        $uploadedFile = $this->resolveUploadedFile($this->data['import_file'] ?? null);
        $originalName = $uploadedFile?->getClientOriginalName();
        $storedPath = null;

        if (! $clinic) {
            Notification::make()
                ->title('Select a clinic first')
                ->body('Choose one clinic from Clinic Scope before importing appointments.')
                ->danger()
                ->send();

            return;
        }

        if (! $uploadedFile instanceof TemporaryUploadedFile) {
            Notification::make()
                ->title('Appointment file is required')
                ->danger()
                ->send();

            return;
        }

        try {
            $storedPath = $this->storeUploadedFile($uploadedFile, $originalName);
            $result = $importService->importFromStoredFile('local', $storedPath, $clinic, $user, $originalName);
        } catch (\Throwable $throwable) {
            Log::warning('Appointment import failed.', [
                'clinic_id' => $clinic->id,
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
        $this->createImportBatch($result, $clinic, $user, $originalName);

        Notification::make()
            ->title('Appointment import completed')
            ->body(($result['imported'] ?? 0) . ' imported, ' . ($result['failed'] ?? 0) . ' failed, ' . ($result['warnings'] ?? 0) . ' warning(s).')
            ->color(($result['failed'] ?? 0) > 0 ? 'warning' : 'success')
            ->send();
    }

    public function getRecentImportBatches()
    {
        $clinic = AdminClinicScope::selectedClinic();

        if (! $clinic) {
            return collect();
        }

        return AppointmentImportBatch::query()
            ->with('user')
            ->where('clinic_id', $clinic->id)
            ->latest()
            ->limit(5)
            ->get();
    }

    public function downloadFailedRows(int $batchId): StreamedResponse
    {
        $batch = AppointmentImportBatch::query()->findOrFail($batchId);
        $rows = $batch->failed_row_results ?? [];

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['row', 'patient', 'appointment_date', 'service', 'error']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['row'] ?? '',
                    $row['patient'] ?? '',
                    $row['date'] ?? '',
                    $row['service'] ?? '',
                    $row['message'] ?? '',
                ]);
            }

            fclose($handle);
        }, 'failed-appointment-import-rows-' . $batch->id . '.csv');
    }

    protected function storeUploadedFile(TemporaryUploadedFile $uploadedFile, ?string $originalName): string
    {
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: pathinfo($originalName ?? '', PATHINFO_EXTENSION) ?: 'csv');
        $storedPath = $uploadedFile->storeAs(
            'imports/appointments',
            Str::uuid()->toString() . '.' . $extension,
            'local',
        );

        if (! is_string($storedPath) || $storedPath === '') {
            throw new \RuntimeException('Upload could not be stored for import.');
        }

        return $storedPath;
    }

    protected function createImportBatch(array $result, $clinic, $user, ?string $originalName): void
    {
        AppointmentImportBatch::query()->create([
            'organization_id' => $clinic->organization_id,
            'clinic_id' => $clinic->id,
            'user_id' => $user?->id,
            'original_filename' => $originalName,
            'total_rows' => $result['total'] ?? 0,
            'imported_rows' => $result['imported'] ?? 0,
            'failed_rows' => $result['failed'] ?? 0,
            'warning_rows' => $result['warnings'] ?? 0,
            'row_results' => $result['row_results'] ?? [],
            'failed_row_results' => $result['failed_row_results'] ?? [],
        ]);
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
