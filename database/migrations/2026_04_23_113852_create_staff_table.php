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
        Schema::create('staff', function (Blueprint $table) {
                $table->id();

                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('location_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();

                $table->string('designation')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        
            // Schema::create('staff', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
