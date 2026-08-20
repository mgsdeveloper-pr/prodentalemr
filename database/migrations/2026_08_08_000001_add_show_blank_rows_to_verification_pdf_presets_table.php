<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_pdf_presets', function (Blueprint $table): void {
            $table->boolean('show_blank_rows')
                ->default(true)
                ->after('question_ids');
        });
    }

    public function down(): void
    {
        Schema::table('verification_pdf_presets', function (Blueprint $table): void {
            $table->dropColumn('show_blank_rows');
        });
    }
};
