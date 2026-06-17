<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class AppointmentImportService
{
    public function importFromStoredFile(string $disk, string $path, Clinic $clinic, ?User $user, ?string $originalName = null): array
    {
        return $this->importFromAbsolutePath(Storage::disk($disk)->path($path), $clinic, $user, $originalName);
    }

    public function importFromAbsolutePath(string $path, Clinic $clinic, ?User $user, ?string $originalName = null): array
    {
        $rows = $this->readRows($path, $originalName);

        if ($rows === []) {
            throw new RuntimeException('The uploaded file does not contain appointment rows.');
        }

        $imported = 0;
        $errors = [];
        $rowResults = [];

        foreach ($rows as $index => $row) {
            try {
                $appointment = $this->importRow($this->normalizeRow($row), $clinic, $user);
                $imported++;

                $rowResults[] = [
                    'row' => $index + 2,
                    'status' => 'imported',
                    'patient' => $appointment->patient?->full_name,
                    'date' => $appointment->appointment_date?->format('M d, Y'),
                    'service' => $appointment->appointment_type,
                    'message' => 'Imported',
                ];
            } catch (\Throwable $throwable) {
                $message = 'Row ' . ($index + 2) . ': ' . $throwable->getMessage();
                $errors[] = $message;

                $rowResults[] = [
                    'row' => $index + 2,
                    'status' => 'failed',
                    'patient' => $row['patient_full_name'] ?? $row['patient_name'] ?? null,
                    'date' => $row['appointment_date'] ?? null,
                    'service' => $row['service'] ?? $row['appointment_type'] ?? null,
                    'message' => $throwable->getMessage(),
                ];
            }
        }

        return [
            'total' => count($rows),
            'imported' => $imported,
            'failed' => count($errors),
            'errors' => $errors,
            'row_results' => $rowResults,
        ];
    }

    protected function importRow(array $row, Clinic $clinic, ?User $user): Appointment
    {
        $location = $this->resolveLocation($clinic, $row['location_name'] ?? null);
        $provider = $this->resolveProvider($clinic, $location, $row['provider_name'] ?? null);
        $patient = $this->resolvePatient($clinic, $location, $row, $user);
        $date = $this->normalizeDate($row['appointment_date'] ?? $row['date'] ?? null);
        $startTime = $this->normalizeTime($row['appointment_time'] ?? $row['start_time'] ?? $row['time'] ?? null) ?: '09:00:00';
        $duration = (int) ($row['duration_minutes'] ?? $row['duration'] ?? 30);
        $service = trim((string) ($row['service'] ?? $row['appointment_type'] ?? ''));

        if (! $date) {
            throw new RuntimeException('Appointment date is required.');
        }

        if ($service === '') {
            throw new RuntimeException('Service is required.');
        }

        $endTime = Carbon::parse($startTime)->addMinutes(max($duration, 15))->format('H:i:s');

        return Appointment::query()->create([
            'organization_id' => $clinic->organization_id,
            'clinic_id' => $clinic->id,
            'location_id' => $location->id,
            'provider_id' => $provider->id,
            'patient_id' => $patient->id,
            'appointment_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => max($duration, 15),
            'status' => $this->normalizeStatus($row['status'] ?? null),
            'verification_status' => Appointment::VERIFICATION_STATUS_NOT_SENT,
            'appointment_type' => $service,
            'notes' => $row['notes'] ?? null,
        ]);
    }

    protected function resolveLocation(Clinic $clinic, mixed $locationName): Location
    {
        $locations = Location::query()
            ->where('clinic_id', $clinic->id)
            ->orderBy('id')
            ->get();

        if (filled($locationName)) {
            $location = $locations->first(fn (Location $location): bool => strcasecmp(trim($location->location_name), trim((string) $locationName)) === 0);

            if ($location) {
                return $location;
            }
        }

        return $locations->first()
            ?? throw new RuntimeException('No clinic location exists for the selected clinic.');
    }

    protected function resolveProvider(Clinic $clinic, Location $location, mixed $providerName): Provider
    {
        $providers = Provider::query()
            ->with('user')
            ->where('organization_id', $clinic->organization_id)
            ->where('clinic_id', $clinic->id)
            ->where('status', true)
            ->orderByRaw('case when location_id = ? then 0 else 1 end', [$location->id])
            ->orderBy('id')
            ->get();

        if (filled($providerName)) {
            $provider = $providers->first(fn (Provider $provider): bool => strcasecmp(trim($provider->display_name), trim((string) $providerName)) === 0);

            if ($provider) {
                return $provider;
            }
        }

        return $providers->first()
            ?? throw new RuntimeException('No active provider exists for the selected clinic.');
    }

    protected function resolvePatient(Clinic $clinic, Location $location, array $row, ?User $user): Patient
    {
        [$firstName, $lastName] = $this->patientNameParts($row);
        $dob = $this->normalizeDate($row['patient_dob'] ?? $row['dob'] ?? null);

        if ($firstName === '' || $lastName === '') {
            throw new RuntimeException('Patient name is required.');
        }

        $scope = Patient::query()
            ->where('organization_id', $clinic->organization_id)
            ->where('clinic_id', $clinic->id);

        if (filled($row['pms_patient_id'] ?? $row['pms_id'] ?? null)) {
            $patient = (clone $scope)
                ->where('pms_patient_id', $row['pms_patient_id'] ?? $row['pms_id'])
                ->first();

            if ($patient) {
                return $patient;
            }
        }

        if (filled($row['email'] ?? null)) {
            $patient = (clone $scope)->where('email', $row['email'])->first();

            if ($patient) {
                return $patient;
            }
        }

        $patient = (clone $scope)
            ->where('first_name', $firstName)
            ->where('last_name', $lastName)
            ->when($dob, fn ($query) => $query->whereDate('dob', $dob))
            ->first();

        if ($patient) {
            return $patient;
        }

        return Patient::query()->create([
            'organization_id' => $clinic->organization_id,
            'clinic_id' => $clinic->id,
            'location_id' => $location->id,
            'created_by' => $user?->id,
            'pms_patient_id' => $row['pms_patient_id'] ?? $row['pms_id'] ?? null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'dob' => $dob,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'status' => true,
        ]);
    }

    protected function patientNameParts(array $row): array
    {
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $lastName = trim((string) ($row['last_name'] ?? ''));

        if ($firstName !== '' && $lastName !== '') {
            return [$firstName, $lastName];
        }

        $fullName = trim((string) ($row['patient_full_name'] ?? $row['patient_name'] ?? ''));
        $parts = preg_split('/\s+/', $fullName) ?: [];

        $firstName = array_shift($parts) ?: '';
        $lastName = implode(' ', $parts);

        return [$firstName, $lastName !== '' ? $lastName : '-'];
    }

    protected function normalizeStatus(mixed $value): string
    {
        $status = Str::of((string) $value)->lower()->trim()->replace([' ', '-'], '_')->toString();

        return in_array($status, ['scheduled', 'confirmed', 'checked_in', 'in_chair', 'completed', 'cancelled', 'no_show'], true)
            ? $status
            : 'scheduled';
    }

    protected function readRows(string $path, ?string $originalName = null): array
    {
        $extension = strtolower(pathinfo($originalName ?: $path, PATHINFO_EXTENSION));

        if ($extension === 'xlsx' || $this->looksLikeXlsx($path)) {
            return $this->readXlsxRows($path);
        }

        if ($extension === 'csv' || $this->looksLikeCsv($path)) {
            return $this->readCsvRows($path);
        }

        throw new RuntimeException('Unsupported file type. Please upload a CSV or Excel file.');
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

            $line = array_slice(array_pad($line, count($headers), null), 0, count($headers));
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
                $sharedStrings[] = isset($si->t)
                    ? (string) $si->t
                    : collect($si->r)->map(fn ($run) => (string) $run->t)->implode('');
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
                $columnIndex = $this->columnLettersToIndex(preg_replace('/\d+/', '', (string) $cell['r']));
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

            $row = array_slice(array_pad($row, count($headers), null), 0, count($headers));
            $dataRows[] = array_combine($headers, $row);
        }

        return $dataRows;
    }

    protected function normalizeRow(array $row): array
    {
        return collect($row)
            ->mapWithKeys(fn ($value, $key) => [$this->normalizeHeader((string) $key) => is_string($value) ? trim($value) : $value])
            ->all();
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

    protected function normalizeDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
            }

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
            if (is_numeric($value)) {
                return Carbon::createFromTime(0)->addSeconds((int) round(((float) $value) * 86400))->format('H:i:s');
            }

            return Carbon::parse((string) $value)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
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

        return $firstLine !== false && (str_contains($firstLine, ',') || str_contains($firstLine, ';') || str_contains($firstLine, "\t"));
    }

    protected function columnLettersToIndex(string $letters): int
    {
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord(strtoupper($letters[$i])) - 64);
        }

        return $index - 1;
    }
}
