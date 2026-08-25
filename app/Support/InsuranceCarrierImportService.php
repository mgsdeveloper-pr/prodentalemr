<?php

namespace App\Support;

use App\Models\InsuranceCarrier;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class InsuranceCarrierImportService
{
    public const SUPPORTED_COLUMNS = [
        'insurance_name',
        'payer_id',
        'payer_phone',
        'claims_address',
        'website',
        'notes',
        'is_active',
    ];

    public function preview(string $path, ?string $originalName = null): array
    {
        return $this->process($path, $originalName, false);
    }

    public function import(string $path, ?string $originalName = null): array
    {
        return $this->process($path, $originalName, true);
    }

    protected function process(string $path, ?string $originalName, bool $persist): array
    {
        $rows = $this->readRows($path, $originalName);

        if ($rows === []) {
            throw new RuntimeException('The uploaded file does not contain any insurance rows.');
        }

        $result = [
            'total' => count($rows),
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'failed' => 0,
            'rows' => [],
        ];

        foreach ($rows as $index => $row) {
            try {
                $normalized = $this->normalizeRow($row);
                $this->validateRow($normalized);
                $carrier = $this->findCarrier($normalized);
                $attributes = $this->attributes($normalized);
                $status = $carrier ? ($this->hasChanges($carrier, $attributes) ? 'updated' : 'unchanged') : 'created';

                if ($persist && $status !== 'unchanged') {
                    if ($carrier) {
                        if ($carrier->trashed()) {
                            $carrier->restore();
                        }

                        $carrier->fill($attributes)->save();
                    } else {
                        InsuranceCarrier::query()->create($attributes);
                    }
                }

                $result[$status]++;
                $result['rows'][] = [
                    'row' => $index + 2,
                    'insurance_name' => $attributes['insurance_name'],
                    'payer_id' => $attributes['payer_id'],
                    'status' => $status,
                    'message' => match ($status) {
                        'created' => 'Ready to add as a new insurance payer.',
                        'updated' => 'Matches an existing payer and will update its central details.',
                        default => 'Already matches the central directory.',
                    },
                ];
            } catch (\Throwable $throwable) {
                $result['failed']++;
                $result['rows'][] = [
                    'row' => $index + 2,
                    'insurance_name' => trim((string) ($row['insurance_name'] ?? '')),
                    'payer_id' => trim((string) ($row['payer_id'] ?? '')),
                    'status' => 'failed',
                    'message' => $throwable->getMessage(),
                ];
            }
        }

        return $result;
    }

    protected function validateRow(array $row): void
    {
        if (blank($row['insurance_name'] ?? null)) {
            throw new RuntimeException('Insurance Name is required.');
        }

        if (filled($row['website'] ?? null) && filter_var($row['website'], FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Website must be a complete valid URL.');
        }
    }

    protected function findCarrier(array $row): ?InsuranceCarrier
    {
        if (filled($row['payer_id'] ?? null)) {
            $carrier = InsuranceCarrier::withTrashed()
                ->whereRaw('LOWER(payer_id) = ?', [mb_strtolower((string) $row['payer_id'])])
                ->first();

            if ($carrier) {
                return $carrier;
            }
        }

        return InsuranceCarrier::withTrashed()
            ->whereRaw('LOWER(insurance_name) = ?', [mb_strtolower((string) $row['insurance_name'])])
            ->first();
    }

    protected function attributes(array $row): array
    {
        return [
            'insurance_name' => trim((string) $row['insurance_name']),
            'payer_id' => $this->nullableString($row['payer_id'] ?? null),
            'payer_phone' => $this->nullableString($row['payer_phone'] ?? null),
            'claims_address' => $this->nullableString($row['claims_address'] ?? null),
            'website' => $this->nullableString($row['website'] ?? null),
            'notes' => $this->nullableString($row['notes'] ?? null),
            'is_active' => $this->normalizeBoolean($row['is_active'] ?? true),
        ];
    }

    protected function hasChanges(InsuranceCarrier $carrier, array $attributes): bool
    {
        $carrier->fill($attributes);

        return $carrier->isDirty() || $carrier->trashed();
    }

    protected function normalizeRow(array $row): array
    {
        return collect($row)
            ->mapWithKeys(fn ($value, $key): array => [
                $this->normalizeHeader((string) $key) => is_string($value) ? trim($value) : $value,
            ])
            ->only(self::SUPPORTED_COLUMNS)
            ->all();
    }

    protected function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        return Str::of($header)
            ->trim()
            ->lower()
            ->replace(['#', '/', '\\', '-', '.', '(', ')'], ' ')
            ->replaceMatches('/\s+/', '_')
            ->trim('_')
            ->toString();
    }

    protected function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    protected function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (blank($value)) {
            return true;
        }

        return in_array(Str::lower(trim((string) $value)), ['1', 'yes', 'true', 'active', 'enabled'], true);
    }

    protected function readRows(string $path, ?string $originalName = null): array
    {
        $extension = strtolower(pathinfo($originalName ?: $path, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => $this->readCsvRows($path),
            'xlsx' => $this->readXlsxRows($path),
            default => throw new RuntimeException('Unsupported file type. Upload a CSV or XLSX file.'),
        };
    }

    protected function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');

        if (! $handle) {
            throw new RuntimeException('Unable to read the uploaded CSV file.');
        }

        $headers = null;
        $rows = [];

        try {
            while (($line = fgetcsv($handle)) !== false) {
                if ($headers === null) {
                    $headers = array_map(fn ($header): string => $this->normalizeHeader((string) $header), $line);
                    continue;
                }

                if (count(array_filter($line, fn ($value): bool => filled($value))) === 0) {
                    continue;
                }

                $line = array_slice(array_pad($line, count($headers), null), 0, count($headers));
                $rows[] = array_combine($headers, $line);
            }
        } finally {
            fclose($handle);
        }

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

            foreach ($xml->si as $item) {
                $sharedStrings[] = isset($item->t)
                    ? (string) $item->t
                    : collect($item->r)->map(fn ($run): string => (string) $run->t)->implode('');
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('The uploaded workbook does not contain a first worksheet.');
        }

        $xml = simplexml_load_string($sheetXml);
        $sheetRows = [];

        foreach ($xml->sheetData->row as $row) {
            $cells = [];

            foreach ($row->c as $cell) {
                $index = $this->columnLettersToIndex(preg_replace('/\d+/', '', (string) $cell['r']));
                $type = (string) $cell['t'];
                $value = match ($type) {
                    's' => $sharedStrings[(int) $cell->v] ?? '',
                    'inlineStr' => (string) ($cell->is->t ?? ''),
                    default => (string) ($cell->v ?? ''),
                };
                $cells[$index] = trim($value);
            }

            if ($cells !== []) {
                ksort($cells);
                $sheetRows[] = array_values($cells);
            }
        }

        if ($sheetRows === []) {
            return [];
        }

        $headers = array_map(fn ($header): string => $this->normalizeHeader((string) $header), $sheetRows[0]);
        $rows = [];

        foreach (array_slice($sheetRows, 1) as $row) {
            if (count(array_filter($row, fn ($value): bool => filled($value))) === 0) {
                continue;
            }

            $row = array_slice(array_pad($row, count($headers), null), 0, count($headers));
            $rows[] = array_combine($headers, $row);
        }

        return $rows;
    }

    protected function columnLettersToIndex(string $letters): int
    {
        $index = 0;

        foreach (str_split(strtoupper($letters)) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }
}
