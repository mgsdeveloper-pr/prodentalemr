<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Providers Table Migration
|--------------------------------------------------------------------------
|
| Dental Provider / Doctor Records
|
| Supports:
| - Multi-tenant architecture
| - Organization → Clinic → Location hierarchy
| - Linked user account
| - NPI Number
| - Tax ID (TIN / EIN)
| - State License Number
| - HIPAA-ready audit structure
|
|--------------------------------------------------------------------------
*/

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Tenant Relationships
            |--------------------------------------------------------------------------
            */

            // Top-level organization (DSO / Practice Group)
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            // Clinic under organization
            $table->foreignId('clinic_id')
                ->constrained()
                ->cascadeOnDelete();

            // Branch / Practice Location
            $table->foreignId('location_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Linked User Account
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Professional Information
            |--------------------------------------------------------------------------
            */

            // Example: General Dentist, Orthodontist, Endodontist
            $table->string('specialization')->nullable();

            // State Dental License Number
            $table->string('license_number')->nullable();

            // National Provider Identifier (USA)
            $table->string('npi_number')->nullable();

            // Tax Identification Number (TIN / EIN)
            $table->string('tax_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};


// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
//     /**
//      * Run the migrations.
//      */
//     public function up(): void
//     {
        
//         Schema::create('providers', function (Blueprint $table) {
//             $table->id();
//             $table->timestamps();
//         });
//     }

//     /**
//      * Reverse the migrations.
//      */
//     public function down(): void
//     {
//         Schema::dropIfExists('providers');
//     }
// };