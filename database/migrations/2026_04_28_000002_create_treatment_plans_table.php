<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('plan_number')->unique();
            $table->string('title')->nullable();
            $table->date('plan_date');
            $table->string('status')->default('proposed');
            $table->string('phase')->nullable();
            $table->string('priority')->default('normal');
            $table->text('notes')->nullable();
            $table->text('acceptance_notes')->nullable();
            $table->date('accepted_at')->nullable();

            $table->decimal('estimated_total', 12, 2)->default(0);
            $table->decimal('estimated_insurance', 12, 2)->default(0);
            $table->decimal('estimated_patient', 12, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plans');
    }
};
