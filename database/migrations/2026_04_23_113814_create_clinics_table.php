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
            Schema::create('clinics', function (Blueprint $table) {
                 $table->id();
                 $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                 $table->string('clinic_name');
                 $table->string('clinic_code')->unique();
                 $table->string('timezone')->default('America/New_York');
                 $table->boolean('status')->default(true);
                 $table->timestamps();
            });

        // Schema::create('clinics', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};