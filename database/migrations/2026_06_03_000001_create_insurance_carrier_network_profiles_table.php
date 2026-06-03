<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_carrier_network_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('insurance_carrier_id')->constrained()->cascadeOnDelete();
            $table->text('participating_provider_summary')->nullable();
            $table->text('non_participating_provider_summary')->nullable();
            $table->string('participating_reimbursement_basis')->nullable();
            $table->string('non_participating_reimbursement_basis')->nullable();
            $table->string('out_of_network_coverage')->nullable();
            $table->string('assignment_of_benefits')->nullable();
            $table->string('reimbursement_destination')->nullable();
            $table->text('balance_billing_note')->nullable();
            $table->text('specialist_rule_notes')->nullable();
            $table->text('verification_tips')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('insurance_carrier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_carrier_network_profiles');
    }
};
