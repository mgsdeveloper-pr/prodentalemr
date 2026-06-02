<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_work_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('managed_billing_service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_service_enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_insurance_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_insurance_claim_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_number')->nullable()->index();
            $table->string('title');
            $table->string('status')->default('unassigned');
            $table->string('outcome_status')->nullable();
            $table->string('priority')->default('normal');
            $table->string('source')->default('manual');
            $table->string('pms_sync_status')->default('not_applicable');
            $table->string('writeback_status')->default('not_requested');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->longText('internal_summary')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_work_items');
    }
};
