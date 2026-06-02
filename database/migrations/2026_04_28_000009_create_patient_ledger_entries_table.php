<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_ledger_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('posted_on');
            $table->string('entry_type')->default('charge');
            $table->string('status')->default('posted');
            $table->string('reference_number')->nullable();
            $table->string('description')->nullable();
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_amount', 12, 2)->default(0);
            $table->decimal('debit_amount', 12, 2)->default(0);
            $table->decimal('credit_amount', 12, 2)->default(0);
            $table->decimal('insurance_portion', 12, 2)->default(0);
            $table->decimal('patient_portion', 12, 2)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_ledger_entries');
    }
};
