<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_insurance_claims', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_insurance_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('claim_number')->unique();
            $table->string('claim_type')->default('claim');
            $table->date('claim_date');
            $table->date('service_date')->nullable();
            $table->date('submitted_at')->nullable();
            $table->string('status')->default('draft');
            $table->string('preauth_number')->nullable();
            $table->string('payer_reference')->nullable();
            $table->decimal('billed_amount', 12, 2)->default(0);
            $table->decimal('estimated_coverage', 12, 2)->default(0);
            $table->decimal('insurance_paid', 12, 2)->default(0);
            $table->decimal('patient_responsibility', 12, 2)->default(0);
            $table->text('procedure_summary')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_insurance_claims');
    }
};
