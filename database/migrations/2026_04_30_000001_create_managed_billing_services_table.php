<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managed_billing_services', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->default('verification');
            $table->text('description')->nullable();
            $table->unsignedInteger('service_level_agreement_hours')->default(24);
            $table->string('default_priority')->default('normal');
            $table->boolean('requires_appointment')->default(false);
            $table->boolean('requires_patient')->default(true);
            $table->boolean('requires_policy')->default(false);
            $table->boolean('requires_claim')->default(false);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_billing_services');
    }
};
