<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_work_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('form_type')->default('full_form');
            $table->string('subscriber_name')->nullable();
            $table->date('subscriber_dob')->nullable();
            $table->string('subscriber_id')->nullable();
            $table->string('insured_relation')->nullable();
            $table->string('cob')->nullable();
            $table->string('insurance_provider_name')->nullable();
            $table->text('insurance_claim_mailing_address')->nullable();
            $table->string('insurance_company_phone_number')->nullable();
            $table->string('payer_id')->nullable();
            $table->date('effective_date')->nullable();
            $table->string('group_name')->nullable();
            $table->string('group_number')->nullable();
            $table->string('network_status')->nullable();
            $table->string('fee_schedule')->nullable();
            $table->string('plan_type')->nullable();
            $table->decimal('annual_maximum', 10, 2)->nullable();
            $table->decimal('annual_maximum_remaining', 10, 2)->nullable();
            $table->decimal('individual_deductible', 10, 2)->nullable();
            $table->decimal('individual_deductible_remaining', 10, 2)->nullable();
            $table->decimal('family_deductible', 10, 2)->nullable();
            $table->decimal('family_deductible_remaining', 10, 2)->nullable();
            $table->unsignedInteger('coverage_diagnostic')->nullable();
            $table->unsignedInteger('coverage_preventive')->nullable();
            $table->unsignedInteger('coverage_basic_restorative')->nullable();
            $table->unsignedInteger('coverage_endodontics')->nullable();
            $table->unsignedInteger('coverage_periodontics')->nullable();
            $table->unsignedInteger('coverage_oral_surgery')->nullable();
            $table->unsignedInteger('coverage_major_restorative')->nullable();
            $table->unsignedInteger('coverage_prosthodontics')->nullable();
            $table->unsignedInteger('coverage_implant')->nullable();
            $table->text('waiting_periods')->nullable();
            $table->text('service_history')->nullable();
            $table->text('ortho_information')->nullable();
            $table->text('verification_notes')->nullable();
            $table->text('quick_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_profiles');
    }
};
