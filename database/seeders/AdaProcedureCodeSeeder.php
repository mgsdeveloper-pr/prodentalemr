<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdaProcedureCodeSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('data/ada_procedure_codes_2026.json');

        if (! is_file($dataPath)) {
            $this->command?->warn('ADA procedure code data file was not found.');

            return;
        }

        $now = now();
        $rows = json_decode((string) file_get_contents($dataPath), true, flags: JSON_THROW_ON_ERROR);

        collect($rows)
            ->map(fn (array $row): array => [
                'procedure_code' => $row['procedure_code'],
                'description' => $row['description'],
                'class' => $row['class'],
                'is_active' => (bool) ($row['is_active'] ?? true),
                'source_year' => 2026,
                'source_document' => '055368',
                'source_page' => $row['source_page'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(250)
            ->each(fn ($chunk) => DB::table('ada_procedure_codes')->upsert(
                $chunk->all(),
                ['procedure_code'],
                [
                    'description',
                    'class',
                    'is_active',
                    'source_year',
                    'source_document',
                    'source_page',
                    'updated_at',
                ],
            ));

        $this->command?->info(count($rows) . ' ADA procedure codes imported.');
    }
}
