<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_insurance_policies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('coverage_priority')->default('primary');
            $table->string('insurance_company');
            $table->string('plan_name')->nullable();
            $table->string('member_id');
            $table->string('group_number')->nullable();
            $table->string('subscriber_name');
            $table->string('subscriber_relationship')->default('self');
            $table->date('subscriber_dob')->nullable();
            $table->string('subscriber_employer')->nullable();
            $table->string('payer_phone')->nullable();
            $table->text('claims_address')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->boolean('status')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_insurance_policies');
    }
};
