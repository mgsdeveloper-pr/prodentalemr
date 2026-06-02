<?php

namespace App\Support;

use App\Filament\Clinic\Resources\VerificationRequests\Schemas\VerificationRequestForm;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\Location;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Provider;
use App\Models\User;
use App\Support\VerificationRequestDuplicateGuard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class VerificationRequestImportService
{
    public const REQUIRED_COLUMNS = [
        'location_name',
        'provider_name',
        'appointment_date',
        'appointment_time',
        'patient_full_name',
        'patient_dob',
        'payer_name',
    ];

    public function importFromStoredFile(string $disk, string $path, Clinic $clinic, User $user, ?string $originalName = null): array
    {
        $absolutePath = Storage::disk($disk)->path($path);

        return $this->importFromAbsolutePath($absolutePath, $clinic, $user, $originalName);
    }

    public function importForAdminFromStoredFile(string $disk, string $path, Clinic $clinic, User $user, ?string $originalName = null): array
    {
        $absolutePath = Storage::disk($disk)->path($path);

        return $this->importFromAbsolutePath($absolutePath, $clinic, $user, $originalName, 'admin');
    }

    public function importFromAbsolutePath(
        string $absolutePath,
        Clinic $clinic,
        User $user,
        ?string $originalName = null,
        string $channel = 'clinic',
    ): array
    {
        $rows = $this->readRows($absolutePath, $originalName);

        if (count($rows) === 0) {
            throw new RuntimeException('The uploaded file does not contain any rows to import.');
        }

        $imported = 0;
        $duplicates = 0;
        $errors = [];
        $rowResults = [];

        foreach ($rows as $index => $row) {
            try {
                $summary = $this->importRow($row, $clinic, $user, $channel);
                if (($summary['status'] ?? 'imported') === 'duplicate') {
                    $duplicates++;
                } else {
                    $imported++;
                }

                $rowResults[] = [
                    'row' => $index + 2,
                    'status' => $summary['status'] ?? 'imported',
                    'patient' => $summary['patient'],
                    'location' => $summary['location'],
                    'provider' => $summary['provider'],
                    'mode' => $summary['mode'],
                    'reference' => $summary['reference'],
                    'existing_url' => $summary['existing_url'] ?? null,
                    'payer' => $summary['payer'] ?? null,
                    'existing_status' => $summary['existing_status'] ?? null,
                    'message' => $summary['message'],
                ];
            } catch (\Throwable $throwable) {
                $message = 'Row ' . ($index + 2) . ': ' . $throwable->getMessage();
                $errors[] = $message;
                $rowResults[] = [
                    'row' => $index + 2,
                    'status' => 'failed',
                    'patient' => $row['patient_full_name'] ?? null,
                    'location' => $row['location_name'] ?? null,
                    'provider' => $row['provider_name'] ?? null,
                    'mode' => null,
                    'reference' => null,
                    'existing_url' => null,
                    'payer' => $row['payer_name'] ?? null,
                    'existing_status' => null,
                    'message' => $throwable->getMessage(),
                ];
            }
        }

        return [
            'total' => count($rows),
            'imported' => $imported,
            'duplicates' => $duplicates,
            'failed' => count($errors),
            'errors' => $errors,
            'row_results' => $rowResults,
        ];
    }

    protected function importRow(array $row, Clinic $clinic, User $user, string $channel = 'clinic'): array
    {
        $row = $this->normalizeRow($row);

        foreach (self::REQUIRED_COLUMNS as $column) {
            if (blank($row[$column] ?? null)) {
                throw new RuntimeException('Missing required value for "' . str($column)->replace('_', ' ')->title() . '".');
            }
        }

        $location = $this->resolveLocation($clinic, (string) $row['location_name']);
        $provider = $this->resolveProvider($clinic, $location, (string) $row['provider_name']);
        $patient = $this->resolvePatient($clinic, $location, $row);
        $policy = $this->resolvePolicy($patient, $location, $row);

        $enrollment = VerificationRequestForm::resolveVerificationEnrollment(
            $clinic->organization_id,
            $clinic->id,
            $location?->id,
        );

        $priority = filled($row['priority'] ?? null) && str((string) $row['priority'])->lower()->toString() === 'urgent'
            ? 'urgent'
            : 'normal';

        $source = $channel === 'admin'
            ? ($enrollment ? 'clinic_request' : 'manual')
            : ($enrollment ? 'clinic_request' : 'clinic_self_service');

        $patientName = (string) $row['patient_full_name'];
        $appointmentDate = (string) $row['appointment_date'];

        $verificationProfileData = [
            'patient_full_name' => $patientName,
            'patient_dob' => $this->normalizeDate($row['patient_dob'] ?? null),
            'appointment_date' => $this->normalizeDate($row['appointment_date'] ?? null),
            'insurance_provider_name' => $row['payer_name'] ?? null,
            'patient_identifier' => $row['patient_identifier'] ?: ($row['member_id'] ?? null),
        ];

        $workItemData = [
            'clinic_id' => $clinic->id,
            'patient_id' => $patient?->id,
        ];

        $duplicateCandidate = VerificationRequestDuplicateGuard::findExisting($workItemData, $verificationProfileData, [[
            'payer_name' => $row['payer_name'] ?? null,
            'member_id' => $row['member_id'] ?? ($policy?->member_id ?? null),
        ]]);

        if ($duplicateCandidate) {
            $duplicate = VerificationRequestDuplicateGuard::duplicatePayload(
                $duplicateCandidate,
                $verificationProfileData,
                $channel === 'admin' ? 'verification' : 'clinic',
            );

            return [
                'status' => 'duplicate',
                'patient' => $patientName,
                'location' => $row['location_name'],
                'provider' => $row['provider_name'],
                'mode' => $channel === 'admin' ? 'service' : 'clinic',
                'reference' => $duplicate['reference'],
                'existing_url' => $duplicate['url'],
                'payer' => $duplicate['payer_label'],
                'existing_status' => $duplicate['status_label'],
                'message' => 'Skipped as duplicate. Existing request '
                    . $duplicate['reference']
                    . ' is already open for '
                    . $duplicate['appointment_date_label']
                    . ' under '
                    . $duplicate['payer_label']
                    . '.',
            ];
        }

        $workItem = BillingWorkItem::create([
            'organization_id' => $clinic->organization_id,
            'clinic_id' => $clinic->id,
            'location_id' => $location?->id,
            'managed_billing_service_id' => $enrollment?->managed_billing_service_id ?: VerificationRequestForm::resolveDefaultVerificationServiceId(),
            'client_service_enrollment_id' => $enrollment?->id,
            'patient_id' => $patient?->id,
            'provider_id' => $provider?->id,
            'patient_insurance_policy_id' => $policy?->id,
            'assigned_to' => VerificationAutoAssigner::resolve($source, $clinic->id)?->id,
            'created_by' => $user->id,
            'title' => trim(collect([
                'Insurance Verification',
                $patientName,
                filled($appointmentDate) ? date('M d, Y', strtotime($appointmentDate)) : null,
            ])->filter()->implode(' - ')),
            'status' => 'pending',
            'outcome_status' => 'pending',
            'priority' => $priority,
            'source' => $source,
            'pms_sync_status' => 'pending',
            'writeback_status' => 'not_requested',
            'due_at' => $enrollment
                ? $enrollment->calculateDueAt($priority)
                : ($priority === 'urgent' ? now()->addHours(24) : now()->addDays(3)),
            'notes' => $row['notes'] ?? null,
        ]);

        $workItem->verificationProfile()->create([
            'form_type' => $this->normalizeFormType($row['form_type'] ?? null),
            'requested_by_name' => $user->name,
            'requested_by_role_slug' => $user->getPrimaryRoleName(),
            'requested_from_panel' => $channel === 'admin' ? 'admin' : 'clinic',
            'patient_full_name' => $patientName,
            'patient_dob' => $this->normalizeDate($row['patient_dob'] ?? null),
            'patient_identifier' => $row['patient_identifier'] ?: ($row['member_id'] ?? null),
            'patient_zip' => $row['patient_zip'] ?? null,
            'appointment_date' => $this->normalizeDate($row['appointment_date'] ?? null),
            'appointment_time' => $this->normalizeTime($row['appointment_time'] ?? null),
            'pms_id' => $row['pms_id'] ?? null,
            'is_pre_registered' => $this->normalizeBoolean($row['is_pre_registered'] ?? null),
            'subscriber_name' => $row['subscriber_name'] ?: ($policy?->subscriber_name ?? null),
            'subscriber_dob' => $this->normalizeDate($row['subscriber_dob'] ?? null),
            'insurance_provider_name' => $row['payer_name'],
            'group_number' => $row['group_number'] ?? null,
            'provider_name' => $row['provider_name'] ?? null,
            'location_name' => $row['location_name'] ?? null,
            'verification_notes' => $row['notes'] ?? null,
        ]);

        $workItem->verificationPlanSnapshots()->create([
            'plan_priority' => $this->normalizePlanPriority($row['plan_priority'] ?? null),
            'payer_name' => $row['payer_name'],
            'member_id' => $row['member_id'] ?? ($policy?->member_id ?? null),
            'group_number' => $row['group_number'] ?? ($policy?->group_number ?? null),
            'subscriber_name' => $row['subscriber_name'] ?: ($policy?->subscriber_name ?? null),
            'subscriber_dob' => $this->normalizeDate($row['subscriber_dob'] ?? null),
            'notes' => $row['notes'] ?? null,
        ]);

        $workItem->recordActivity('verification_profile_saved', 'Structured verification request details captured from import.');

        if ($channel === 'admin') {
            $workItem->recordActivity('admin_import_created', 'Insurance verification was imported directly into the Admin verification queue.', [
                'panel' => 'admin',
                'user_name' => $user->name,
            ]);
        } elseif ($workItem->source === 'clinic_request') {
            $workItem->recordActivity('managed_service_requested', 'Insurance verification was imported from the clinic portal to the Admin verification queue.');
        } else {
            $workItem->recordActivity('clinic_self_service_created', 'Insurance verification was imported from the clinic portal for self-service use.');
        }

        return [
            'status' => 'imported',
            'patient' => $patientName,
            'location' => $row['location_name'],
            'provider' => $row['provider_name'],
            'mode' => in_array($workItem->source, ['clinic_request', 'manual'], true) ? 'service' : 'clinic',
            'reference' => $workItem->reference_number,
            'existing_url' => null,
            'payer' => $row['payer_name'],
            'existing_status' => BillingWorkItem::STATUS_OPTIONS[$workItem->normalized_status] ?? str($workItem->normalized_status)->replace('_', ' ')->title()->toString(),
            'message' => $channel === 'admin'
                ? 'Imported directly into the Admin verification queue.'
                : ($workItem->source === 'clinic_request'
                    ? 'Imported and routed to the Admin service queue.'
                    : 'Imported as a clinic self-service verification request.'),
        ];
    }

    protected function resolveLocation(Clinic $clinic, string $locationName): ?Location
    {
        return Location::query()
            ->where('clinic_id', $clinic->id)
            ->get()
            ->first(fn (Location $location): bool => strcasecmp(trim($location->location_name), trim($locationName)) === 0)
            ?? throw new RuntimeException("Location '{$locationName}' was not found in the selected clinic.");
    }

    protected function resolveProvider(Clinic $clinic, ?Location $location, string $providerName): ?Provider
    {
        $providers = Provider::query()
            ->with('user')
            ->where('organization_id', $clinic->organization_id)
            ->where('clinic_id', $clinic->id)
            ->when($location, fn ($query) => $query->where('location_id', $location->id))
            ->get();

        $provider = $providers->first(fn (Provider $provider): bool => strcasecmp(trim($provider->display_name), trim($providerName)) === 0);

        return $provider ?? throw new RuntimeException("Provider '{$providerName}' was not found for the selected clinic/location.");
    }

    protected function resolvePatient(Clinic $clinic, ?Location $location, array $row): ?Patient
    {
        if ($location === null) {
            return null;
        }

        $scope = Patient::query()
            ->where('organization_id', $clinic->organization_id)
            ->where('clinic_id', $clinic->id)
            ->where('location_id', $location->id);

        if (filled($row['pms_id'] ?? null)) {
            return (clone $scope)->where('pms_patient_id', $row['pms_id'])->first();
        }

        if (filled($row['member_id'] ?? null)) {
            $policy = PatientInsurancePolicy::query()
                ->with('patient')
                ->where('organization_id', $clinic->organization_id)
                ->where('clinic_id', $clinic->id)
                ->where(function ($query) use ($location): void {
                    $query->whereNull('location_id')->orWhere('location_id', $location->id);
                })
                ->where('member_id', $row['member_id'])
                ->first();

            if ($policy?->patient) {
                return $policy->patient;
            }
        }

        $parts = preg_split('/\s+/', trim((string) $row['patient_full_name'])) ?: [];
        $firstName = array_shift($parts) ?? null;
        $lastName = count($parts) > 0 ? implode(' ', $parts) : null;

        return (clone $scope)
            ->whereDate('dob', $this->normalizeDate($row['patient_dob']))
            ->when(filled($firstName), fn ($query) => $query->where('first_name', 'like', $firstName))
            ->when(filled($lastName), fn ($query) => $query->where('last_name', 'like', $lastName))
            ->first();
    }

    protected function resolvePolicy(?Patient $patient, ?Location $location, array $row): ?PatientInsurancePolicy
    {
        if (! $patient) {
            return null;
        }

        return $patient->insurancePolicies()
            ->when($location, fn ($query) => $query->where(function ($innerQuery) use ($location): void {
                $innerQuery->whereNull('location_id')->orWhere('location_id', $location->id);
            }))
            ->when(filled($row['member_id'] ?? null), fn ($query) => $query->where('member_id', $row['member_id']))
            ->orderByRaw("case when coverage_priority = 'primary' then 0 when coverage_priority = 'secondary' then 1 else 2 end")
            ->first();
    }

    protected function readRows(string $path, ?string $originalName = null): array
    {
        $extension = strtolower(pathinfo($originalName ?: $path, PATHINFO_EXTENSION));

        if ($extension === 'xlsx') {
            return $this->readXlsxRows($path);
        }

        if ($extension === 'csv') {
            return $this->readCsvRows($path);
        }

        if ($this->looksLikeXlsx($path)) {
            return $this->readXlsxRows($path);
        }

        if ($this->looksLikeCsv($path)) {
            return $this->readCsvRows($path);
        }

        throw new RuntimeException('Unsupported file type. Please upload an .xlsx or .csv file.');
    }

    protected function looksLikeXlsx(string $path): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return false;
        }

        $hasWorkbook = $zip->locateName('xl/workbook.xml') !== false;
        $zip->close();

        return $hasWorkbook;
    }

    protected function looksLikeCsv(string $path): bool
    {
        $handle = @fopen($path, 'rb');

        if (! $handle) {
            return false;
        }

        $firstLine = fgets($handle);
        fclose($handle);

        if ($firstLine === false) {
            return false;
        }

        return str_contains($firstLine, ',') || str_contains($firstLine, ';') || str_contains($firstLine, "\t");
    }

    protected function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');

        if (! $handle) {
            throw new RuntimeException('Unable to read the uploaded CSV file.');
        }

        $headers = null;
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $line);
                continue;
            }

            if (count(array_filter($line, fn ($value) => filled($value))) === 0) {
                continue;
            }

            $rows[] = array_combine($headers, array_map(fn ($value) => is_string($value) ? trim($value) : $value, $line));
        }

        fclose($handle);

        return $rows;
    }

    protected function readXlsxRows(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open the uploaded Excel file.');
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedStringsXml !== false) {
            $xml = simplexml_load_string($sharedStringsXml);

            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                    continue;
                }

                $parts = [];

                foreach ($si->r as $run) {
                    $parts[] = (string) $run->t;
                }

                $sharedStrings[] = implode('', $parts);
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('The uploaded Excel file does not contain the first worksheet.');
        }

        $xml = simplexml_load_string($sheetXml);
        $rows = [];

        foreach ($xml->sheetData->row as $row) {
            $cells = [];

            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                $columnLetters = preg_replace('/\d+/', '', $reference);
                $columnIndex = $this->columnLettersToIndex($columnLetters);
                $type = (string) $cell['t'];

                $value = match ($type) {
                    's' => $sharedStrings[(int) $cell->v] ?? '',
                    'inlineStr' => (string) ($cell->is->t ?? ''),
                    default => (string) ($cell->v ?? ''),
                };

                $cells[$columnIndex] = trim($value);
            }

            if ($cells !== []) {
                ksort($cells);
                $rows[] = array_values($cells);
            }
        }

        if ($rows === []) {
            return [];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $rows[0]);
        $dataRows = [];

        foreach (array_slice($rows, 1) as $row) {
            if (count(array_filter($row, fn ($value) => filled($value))) === 0) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $dataRows[] = array_combine($headers, $row);
        }

        return $dataRows;
    }

    protected function normalizeHeader(string $header): string
    {
        return Str::of($header)
            ->trim()
            ->lower()
            ->replace(['#', '/', '\\', '-', '.', '(', ')'], ' ')
            ->replaceMatches('/\s+/', '_')
            ->trim('_')
            ->toString();
    }

    protected function normalizeRow(array $row): array
    {
        return collect($row)
            ->mapWithKeys(fn ($value, $key) => [$key => is_string($value) ? trim($value) : $value])
            ->all();
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeTime(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('H:i');
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    protected function normalizeFormType(mixed $value): string
    {
        return str((string) $value)->lower()->contains('short')
            ? 'short_form'
            : 'full_form';
    }

    protected function normalizePlanPriority(mixed $value): string
    {
        $normalized = str((string) $value)->lower()->trim()->toString();

        return in_array($normalized, ['primary', 'secondary', 'tertiary'], true)
            ? $normalized
            : 'primary';
    }

    protected function normalizeBoolean(mixed $value): bool
    {
        return in_array(str((string) $value)->lower()->trim()->toString(), ['1', 'yes', 'y', 'true'], true);
    }

    protected function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }
}
