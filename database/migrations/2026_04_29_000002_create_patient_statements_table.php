<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_statements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('statement_number')->unique();
            $table->date('statement_date');
            $table->date('period_from');
            $table->date('period_to');
            $table->string('status')->default('draft');
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('charges_total', 12, 2)->default(0);
            $table->decimal('payments_total', 12, 2)->default(0);
            $table->decimal('adjustments_total', 12, 2)->default(0);
            $table->decimal('closing_balance', 12, 2)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_statements');
    }
};
