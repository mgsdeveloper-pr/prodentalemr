<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('verification_form_questions')) {
            DB::table('verification_form_questions')
                ->where('template_key', 'template_2')
                ->update(['template_key' => 'template_3']);

            DB::table('verification_form_questions')
                ->where('section_key', 'like', 'template_2_%')
                ->orderBy('id')
                ->select(['id', 'section_key'])
                ->get()
                ->each(function (object $row): void {
                    DB::table('verification_form_questions')
                        ->where('id', $row->id)
                        ->update(['section_key' => 'template_3_' . substr($row->section_key, strlen('template_2_'))]);
                });
        }

        if (Schema::hasTable('verification_template_sections')) {
            DB::table('verification_template_sections')
                ->where('template_key', 'template_2')
                ->update(['template_key' => 'template_3']);

            foreach (['section_key', 'parent_section_key'] as $column) {
                if (! Schema::hasColumn('verification_template_sections', $column)) {
                    continue;
                }

                DB::table('verification_template_sections')
                    ->where($column, 'like', 'template_2_%')
                    ->orderBy('id')
                    ->select(['id', $column])
                    ->get()
                    ->each(function (object $row) use ($column): void {
                        DB::table('verification_template_sections')
                            ->where('id', $row->id)
                            ->update([$column => 'template_3_' . substr($row->{$column}, strlen('template_2_'))]);
                    });
            }
        }
    }

    public function down(): void
    {
        // Legacy template rows are intentionally not recreated.
    }
};
