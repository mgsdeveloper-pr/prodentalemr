<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_consent_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('form_type');
            $table->string('title');
            $table->string('status')->default('draft');
            $table->date('document_date')->nullable();
            $table->date('signed_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('signed_by_name')->nullable();
            $table->string('relationship_to_patient')->nullable();
            $table->string('typed_signature')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->longText('body_text')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_consent_forms');
    }
};
