<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->string('verification_pdf_output_mode', 32)
                ->default('standard')
                ->after('status');
            $table->json('verification_pdf_output_sections')
                ->nullable()
                ->after('verification_pdf_output_mode');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->dropColumn([
                'verification_pdf_output_mode',
                'verification_pdf_output_sections',
            ]);
        });
    }
};
