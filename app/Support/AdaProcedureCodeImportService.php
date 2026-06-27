<?php

namespace App\Support;

use App\Models\AdaProcedureCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class AdaProcedureCodeImportService
{
    public function previewFromStoredFile(string $disk, string $path, ?string $originalName = null): array
    {
        return $this->previewFromAbsolutePath(Storage::disk($disk)->path($path), $originalName);
    }

    public function previewFromAbsolutePath(string $path, ?string $originalName = null): array
    {
        $rows = $this->readRows($path, $originalName);

        if ($rows === []) {
            throw new RuntimeException('The uploaded file does not contain ADA/CDT rows.');
        }

        $existingCodes = AdaProcedureCode::query()
            ->pluck('procedure_code')
            ->map(fn (string $code): string => strtoupper(trim($code)))
            ->all();

        $existingCodeLookup = array_fill_keys($existingCodes, true);
        $seenInFile = [];
        $rowResults = [];
        $ready = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($rows as $index => $row) {
            $normalizedRow = $this->normalizeRow($row);
            $result = $this->previewRow($normalizedRow, $existingCodeLookup, $seenInFile);
            $result['row'] = $index + 2;

            if (($result['status'] ?? null) === 'ready') {
                $ready++;
            } elseif (($result['status'] ?? null) === 'skipped') {
                $skipped++;
            } else {
                $failed++;
            }

            $rowResults[] = $result;
        }

        return [
            'total' => count($rows),
            'ready' => $ready,
            'skipped' => $skipped,
            'failed' => $failed,
            'row_results' => $rowResults,
        ];
    }

    public function importFromStoredFile(string $disk, string $path, ?string $originalName = null): array
    {
        return $this->importFromAbsolutePath(Storage::disk($disk)->path($path), $originalName);
    }

    public function importFromAbsolutePath(string $path, ?string $originalName = null): array
    {
        $rows = $this->readRows($path, $originalName);

        if ($rows === []) {
            throw new RuntimeException('The uploaded file does not contain ADA/CDT rows.');
        }

        $existingCodes = AdaProcedureCode::query()
            ->pluck('procedure_code')
            ->map(fn (string $code): string => strtoupper(trim($code)))
            ->all();

        $existingCodeLookup = array_fill_keys($existingCodes, true);
        $seenInFile = [];
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $rowResults = [];
        $failedRowResults = [];

        foreach ($rows as $index => $row) {
            $normalizedRow = $this->normalizeRow($row);
            $preview = $this->previewRow($normalizedRow, $existingCodeLookup, $seenInFile);
            $preview['row'] = $index + 2;

            if (($preview['status'] ?? null) === 'failed') {
                $failed++;
                $rowResults[] = $preview;
                $failedRowResults[] = $preview;
                continue;
            }

            if (($preview['status'] ?? null) === 'skipped') {
                $skipped++;
                $rowResults[] = $preview;
                continue;
            }

            AdaProcedureCode::query()->create([
                'procedure_code' => $preview['code'],
                'description' => $preview['description'],
                'class' => $this->normalizeClass($normalizedRow['class'] ?? $normalizedRow['category'] ?? null),
                'is_active' => true,
                'source_year' => (int) date('Y'),
                'source_document' => $originalName,
            ]);

            $existingCodeLookup[$preview['code']] = true;
            $seenInFile[$preview['code']] = true;
            $imported++;

            $rowResults[] = [
                'row' => $index + 2,
                'status' => 'imported',
                'code' => $preview['code'],
                'description' => $preview['description'],
                'message' => 'Imported successfully.',
            ];
        }

        return [
            'total' => count($rows),
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'row_results' => $rowResults,
            'failed_row_results' => $failedRowResults,
        ];
    }

    protected function previewRow(array $row, array $existingCodeLookup, array $seenInFile): array
    {
        $code = $this->normalizeCode($row['code'] ?? $row['codes'] ?? $row['ada_cdt_code'] ?? $row['procedure_code'] ?? null);
        $description = trim((string) ($row['description'] ?? $row['descriptions'] ?? ''));

        if ($code === '') {
            return [
                'status' => 'failed',
                'code' => '',
                'description' => $description,
                'message' => 'Code is required.',
            ];
        }

        if ($description === '') {
            return [
                'status' => 'failed',
                'code' => $code,
                'description' => '',
                'message' => 'Description is required.',
            ];
        }

        if (isset($seenInFile[$code])) {
            return [
                'status' => 'skipped',
                'code' => $code,
                'description' => $description,
                'message' => 'Duplicate code found inside this file.',
            ];
        }

        if (isset($existingCodeLookup[$code])) {
            return [
                'status' => 'skipped',
                'code' => $code,
                'description' => $description,
                'message' => 'Code already exists in the ADA/CDT master list.',
            ];
        }

        return [
            'status' => 'ready',
            'code' => $code,
            'description' => $description,
            'message' => 'Ready to import.',
        ];
    }

    protected function normalizeClass(mixed $value): ?string
    {
        $class = trim((string) $value);

        return $class !== '' ? $class : null;
    }

    protected function normalizeCode(mixed $value): string
    {
        return strtoupper(trim((string) $value));
    }

    protected function readRows(string $path, ?string $originalName = null): array
    {
        $extension = strtolower(pathinfo($originalName ?: $path, PATHINFO_EXTENSION));

        if ($extension === 'xlsx' || $this->looksLikeXlsx($path)) {
            return $this->readXlsxRows($path);
        }

        if (in_array($extension, ['csv', 'txt'], true) || $this->looksLikeCsv($path)) {
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
