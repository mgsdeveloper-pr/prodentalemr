<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_pdf_presets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('output_mode', 32)->default('standard');
            $table->json('section_keys')->nullable();
            $table->json('question_ids')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['clinic_id', 'is_default']);
            $table->index(['clinic_id', 'is_active']);
        });

        Schema::table('clinics', function (Blueprint $table): void {
            $table->foreignId('default_verification_pdf_preset_id')
                ->nullable()
                ->after('verification_pdf_output_question_ids')
                ->constrained('verification_pdf_presets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('default_verification_pdf_preset_id');
        });

        Schema::dropIfExists('verification_pdf_presets');
    }
};
