<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('patient_insurance_claim_line_items');

        Schema::create('patient_insurance_claim_line_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_insurance_claim_id');
            $table->unsignedBigInteger('treatment_plan_item_id')->nullable();
            $table->unsignedBigInteger('service_item_id')->nullable();
            $table->string('procedure_code')->nullable();
            $table->string('description');
            $table->string('tooth_number', 50)->nullable();
            $table->string('tooth_surface', 50)->nullable();
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_fee', 10, 2)->default(0);
            $table->decimal('billed_amount', 10, 2)->default(0);
            $table->decimal('estimated_coverage', 10, 2)->default(0);
            $table->decimal('insurance_paid', 10, 2)->default(0);
            $table->decimal('patient_responsibility', 10, 2)->default(0);
            $table->string('status')->default('ready');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('patient_insurance_claim_id', 'picli_claim_fk')
                ->references('id')
                ->on('patient_insurance_claims')
                ->cascadeOnDelete();
            $table->foreign('treatment_plan_item_id', 'picli_tp_item_fk')
                ->references('id')
                ->on('treatment_plan_items')
                ->nullOnDelete();
            $table->foreign('service_item_id', 'picli_service_item_fk')
                ->references('id')
                ->on('service_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_insurance_claim_line_items');
    }
};
