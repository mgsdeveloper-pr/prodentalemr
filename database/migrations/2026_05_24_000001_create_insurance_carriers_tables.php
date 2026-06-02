<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_carriers', function (Blueprint $table): void {
            $table->id();
            $table->string('insurance_name');
            $table->string('payer_id')->nullable();
            $table->string('payer_phone')->nullable();
            $table->text('claims_address')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('clinic_insurance_carrier_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_carrier_id')->constrained()->cascadeOnDelete();
            $table->string('insurance_name')->nullable();
            $table->string('payer_id')->nullable();
            $table->string('payer_phone')->nullable();
            $table->text('claims_address')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamps();

            $table->unique(['clinic_id', 'insurance_carrier_id'], 'clinic_carrier_override_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_insurance_carrier_overrides');
        Schema::dropIfExists('insurance_carriers');
    }
};
