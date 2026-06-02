<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tooth_number')->nullable();
            $table->string('tooth_surface')->nullable();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_fee', 12, 2)->default(0);
            $table->decimal('estimated_insurance', 12, 2)->default(0);
            $table->decimal('estimated_patient', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->string('status')->default('proposed');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_items');
    }
};
