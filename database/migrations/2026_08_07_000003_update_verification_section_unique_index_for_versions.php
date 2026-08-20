<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_template_sections', function (Blueprint $table): void {
            if ($this->indexExists('verification_template_sections', 'verification_template_sections_unique')) {
                $table->dropUnique('verification_template_sections_unique');
            }

            if (! $this->indexExists('verification_template_sections', 'verification_template_sections_version_unique')) {
                $table->unique(
                    ['clinic_id', 'template_version_id', 'template_key', 'section_key'],
                    'verification_template_sections_version_unique',
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('verification_template_sections', function (Blueprint $table): void {
            if ($this->indexExists('verification_template_sections', 'verification_template_sections_version_unique')) {
                $table->dropUnique('verification_template_sections_version_unique');
            }

            if (! $this->indexExists('verification_template_sections', 'verification_template_sections_unique')) {
                $table->unique(['clinic_id', 'template_key', 'section_key'], 'verification_template_sections_unique');
            }
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn (object $row): bool => ($row->name ?? null) === $index);
        }

        return collect(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
