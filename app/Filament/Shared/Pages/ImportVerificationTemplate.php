<?php

namespace App\Filament\Shared\Pages;

use App\Models\Clinic;
use App\Support\VerificationTemplateImport;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

abstract class ImportVerificationTemplate extends Page
{
    use WithFileUploads;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'import-verification-template';

    protected static ?string $title = 'Import Template';

    protected string $view = 'filament.shared.pages.import-verification-template';

    public $upload;

    public string $templateName = '';

    public string $formType = '';

    public array $mappings = [];

    public bool $confirmed = false;

    #[Locked]
    public array $sourceRows = [];

    #[Locked]
    public ?int $clinicId = null;

    #[Locked]
    public array $review = [];

    #[Locked]
    public ?int $createdVersionId = null;

    #[Locked]
    public string $importToken = '';

    abstract protected static function scopedClinic(): ?Clinic;

    abstract protected static function isMasterImport(): bool;

    abstract public function builderUrl(): string;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user || (! static::isMasterImport() && ! static::scopedClinic())) {
            return false;
        }
        try {
            app(VerificationTemplateImport::class)->authorize($user, static::scopedClinic());

            return true;
        } catch (HttpException $e) {
            return false;
        }
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->clinicId = static::scopedClinic()?->id;
        $this->importToken = (string) \Illuminate\Support\Str::uuid();
    }

    protected function authorizeScope(): void
    {
        abort_unless(static::canAccess() && $this->clinicId === static::scopedClinic()?->id, 403);
    }

    public function scopeLabel(): string
    {
        return static::isMasterImport() ? 'Platform Master Template' : (static::scopedClinic()?->clinic_name ?? 'No clinic selected');
    }

    public function getBreadcrumbs(): array
    {
        return [$this->builderUrl() => 'Template Builder', 'Import'];
    }

    public function updatedUpload(): void
    {
        $this->importToken = (string) \Illuminate\Support\Str::uuid();
        $this->review = [];
        $this->sourceRows = [];
        $this->mappings = [];
        $this->confirmed = false;
        $this->createdVersionId = null;
        $this->resetValidation();
    }

    public function updatedFormType(): void
    {
        $this->updatedUpload();
    }

    public function updatedMappings(): void
    {
        $this->review = [];
        $this->confirmed = false;
    }

    public function validateMappings(): void
    {
        $this->authorizeScope();
        $this->confirmed = false;
        $this->review = app(VerificationTemplateImport::class)->reviewMappings($this->sourceRows, $this->mappings, $this->formType);
    }

    public function preview(): void
    {
        $this->authorizeScope();
        $this->review = [];
        $this->validate(['upload' => 'required|file|max:5120|mimes:xlsx,csv,txt', 'formType' => 'required|in:short_form,full_form']);
        $this->sourceRows = [];
        $this->mappings = [];
        $this->confirmed = false;
        try {
            $this->review = app(VerificationTemplateImport::class)->read($this->upload->getRealPath(), $this->upload->getClientOriginalName());
            if (empty($this->review['errors'])) {
                $this->sourceRows = $this->review['rows'];
                foreach ($this->sourceRows as $index => $row) {
                    $this->mappings[$index] = VerificationTemplateImport::mappedField($row)['field_key'] ?? '';
                }
                $this->validateMappings();
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->addError('upload', 'This file could not be read. Use an unprotected XLSX or UTF-8 CSV matching the sample.');
        }
    }

    public function importDraft(): void
    {
        $this->authorizeScope();
        if ($this->createdVersionId) {
            return;
        }
        $this->validate(['templateName' => 'required|string|max:255']);
        if (! count($this->review['rows'] ?? []) || count($this->review['errors'] ?? [])) {
            $this->addError('upload', 'Preview a valid file before creating the draft.');

            return;
        }
        $this->validate(['confirmed' => 'accepted']);
        $this->review = app(VerificationTemplateImport::class)->reviewMappings($this->sourceRows, $this->mappings, $this->formType);
        if ($this->review['errors']) {
            $this->addError('upload', 'Resolve the mapping errors before creating the draft.');
            return;
        }
        $draft = app(VerificationTemplateImport::class)->createDraft(auth()->user(), static::scopedClinic(), $this->templateName, $this->review['rows'], $this->importToken);
        $this->createdVersionId = $draft->id;
        $this->upload?->delete();
        $this->upload = null;
        Notification::make()->title('Template draft created')->success()->send();
    }

    public function downloadSample()
    {
        $this->authorizeScope();

        $this->validate(['formType' => 'required|in:short_form,full_form']);
        $rows = app(VerificationTemplateImport::class)->sampleRows();
        foreach ($rows as &$row) {
            $row[6] = $this->formType;
        }
        return $this->downloadWorkbook($rows, 'verification-template-'.$this->formType.'.xlsx');
    }

    public function recentImports()
    {
        $this->authorizeScope();
        return \App\Models\VerificationTemplateImportReceipt::query()
            ->where('user_id', auth()->id())->where('clinic_id', $this->clinicId)
            ->latest('id')->limit(10)->get();
    }

    public function downloadV2Sample()
    {
        $this->authorizeScope();
        $this->validate(['formType' => 'required|in:short_form,full_form']);
        $row = ['template_3_frequency_percentage', 'exam_frequency', 'How often is D0120 covered?', 'text', 'no', '', $this->formType,
            '2', 'Frequency & Percentage', 'custom_exams', 'Exams', '70', '71', '10', 'CDT', 'D0120', 'frequency', 'individual'];
        return $this->downloadWorkbook([$row], 'verification-template-v2.xlsx', VerificationTemplateImport::V2_HEADERS);
    }

    public function downloadV3Sample()
    {
        $this->authorizeScope();
        $this->validate(['formType' => 'required|in:short_form,full_form']);
        $rows = [
            ['custom_patient', 'vf_patient_full_name', 'Patient name', 'text', 'no', '', $this->formType, '3', 'Patient & Appointment Information', 'custom_patient_provider', 'Patient / Provider', 10, 10, 10, '', '', 'other', '', '', '', '', '', '', ''],
            ['custom_maximums', 'vf_coverage_diagnostic_deductible_applies', 'Diagnostic & Preventive', 'yes_no', 'no', '', $this->formType, '3', 'Maximums & Deductibles', 'custom_category', 'Coverage by Category', 30, 30, 10, '', '', 'coverage', '', 'vf_coverage_diagnostic', 'percent', '', '', '', ''],
            ['custom_frequency', 'exam_benefit', 'Regular Oral Exams (D0120)', 'frequency_row', 'no', '', $this->formType, '3', 'Frequency & Percentage', 'custom_diagnostic', 'Diagnostic & Preventative', 50, 10, 10, 'CDT', 'D0120', 'frequency', 'individual', '', '', 'frequency', 'advanced', 'age_limit|pre_auth_required|notes', 'Diagnostic & Preventative'],
        ];
        return $this->downloadWorkbook($rows, 'verification-template-v3.xlsx', VerificationTemplateImport::V3_HEADERS);
    }

    public function exportReviewed()
    {
        $this->authorizeScope();
        abort_unless(count($this->review['rows'] ?? []) && empty($this->review['errors']), 422);
        $headers = match ($this->review['rows'][0]['format_version'] ?? '1') {
            '3' => VerificationTemplateImport::V3_HEADERS,
            '2' => VerificationTemplateImport::V2_HEADERS,
            default => VerificationTemplateImport::HEADERS,
        };
        $rows = array_map(fn ($row) => array_map(fn ($key) => $row[$key] ?? '', $headers), $this->review['rows']);

        return $this->downloadWorkbook($rows, 'verification-template-reviewed.xlsx', $headers);
    }

    protected function downloadWorkbook(array $rows, string $name, array $headers = VerificationTemplateImport::HEADERS)
    {
        $path = tempnam(sys_get_temp_dir(), 'template-');
        try {
            app(VerificationTemplateImport::class)->writeWorkbook($path, $rows, $headers);
        } catch (Throwable $e) {
            @unlink($path);
            throw $e;
        }

        return response()->download($path, $name)->deleteFileAfterSend(true);
    }
}
