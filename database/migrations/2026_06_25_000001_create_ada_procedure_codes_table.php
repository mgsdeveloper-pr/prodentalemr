<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ada_procedure_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('procedure_code', 10)->unique();
            $table->text('description');
            $table->string('class', 150)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('source_year')->default(2026);
            $table->string('source_document')->default('055368');
            $table->unsignedSmallInteger('source_page')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'procedure_code']);
        });

        $dataPath = database_path('data/ada_procedure_codes_2026.json');

        if (! is_file($dataPath)) {
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
            ->each(fn ($chunk) => DB::table('ada_procedure_codes')->insert($chunk->all()));
    }

    public function down(): void
    {
        Schema::dropIfExists('ada_procedure_codes');
    }
};
