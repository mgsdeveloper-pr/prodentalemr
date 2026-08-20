<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ada_procedure_codes') || Schema::hasColumn('ada_procedure_codes', 'public_id')) {
            return;
        }

        Schema::table('ada_procedure_codes', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->after('id');
        });

        DB::table('ada_procedure_codes')
            ->whereNull('public_id')
            ->orderBy('id')
            ->select('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('ada_procedure_codes')
                        ->where('id', $row->id)
                        ->whereNull('public_id')
                        ->update(['public_id' => (string) Str::ulid()]);
                }
            });

        Schema::table('ada_procedure_codes', function (Blueprint $table): void {
            $table->unique('public_id');
        });

        if (DB::getDriverName() === 'mysql' && ! DB::table('ada_procedure_codes')->whereNull('public_id')->exists()) {
            DB::statement('ALTER TABLE `ada_procedure_codes` MODIFY `public_id` CHAR(26) NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ada_procedure_codes') || ! Schema::hasColumn('ada_procedure_codes', 'public_id')) {
            return;
        }

        Schema::table('ada_procedure_codes', function (Blueprint $table): void {
            $table->dropUnique('ada_procedure_codes_public_id_unique');
            $table->dropColumn('public_id');
        });
    }
};
