<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->json('verification_pdf_output_question_ids')
                ->nullable()
                ->after('verification_pdf_output_sections');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->dropColumn('verification_pdf_output_question_ids');
        });
    }
};
