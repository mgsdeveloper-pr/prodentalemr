<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
                $table->id();

                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('location_id')->constrained()->cascadeOnDelete();

                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('provider_id')->constrained()->cascadeOnDelete();

                $table->date('appointment_date');
                $table->time('start_time');
                $table->time('end_time')->nullable();
                $table->string('status')->default('scheduled');
                $table->string('appointment_type')->nullable();
                $table->text('notes')->nullable();

                $table->timestamps();
            });

        // Schema::create('appointments', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
