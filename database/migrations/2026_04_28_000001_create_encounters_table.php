<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();

            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('encounter_date');
            $table->string('status')->default('draft');
            $table->string('chief_complaint')->nullable();

            $table->longText('subjective_note')->nullable();
            $table->longText('objective_note')->nullable();
            $table->longText('assessment_note')->nullable();
            $table->longText('plan_note')->nullable();

            $table->string('blood_pressure')->nullable();
            $table->string('heart_rate')->nullable();
            $table->string('temperature')->nullable();
            $table->string('weight')->nullable();

            $table->longText('prescriptions')->nullable();
            $table->longText('follow_up_instructions')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
