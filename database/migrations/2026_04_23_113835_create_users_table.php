<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Users Table Migration
|--------------------------------------------------------------------------
|
| Final Production Structure for Dental EMR/EHR SaaS
|
| FLOW:
|
| Public Signup:
| Clinic Owner registers from website
| → Creates Organization
| → Creates Clinic
| → Creates First Owner User
| → Assign Role: clinic_admin
|
| Internal Creation:
| SaaS Admin creates:
| - saas_admin
| - saas_manager
| - saas_user
|
| Clinic Owner creates:
| - clinic_manager
| - doctor
| - receptionist
| - staff
|
| Roles handled using:
| Spatie Laravel Permission
|
| NO user_type ENUM
| YES created_by tracking
| YES multi-tenant structure
| YES HIPAA-ready audit support
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
        /*
        |--------------------------------------------------------------------------
        | Users Table
        |--------------------------------------------------------------------------
        */

        Schema::create('users', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Basic User Information
            |--------------------------------------------------------------------------
            */

            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Multi-Tenant Relationships
            |--------------------------------------------------------------------------
            */

            // Top-level ownership (DSO / Practice Group)
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Clinic under organization
            $table->foreignId('clinic_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Branch / Practice Location
            $table->foreignId('location_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Created By Tracking
            |--------------------------------------------------------------------------
            |
            | Example:
            | SaaS Admin creates SaaS Manager
            | Clinic Owner creates Doctor
            |
            | Helps with:
            | - Audit Trails
            | - HIPAA Compliance
            | - Security Review
            |
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Account Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('status')->default(true);

            /*
            |--------------------------------------------------------------------------
            | Authentication
            |--------------------------------------------------------------------------
            */

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->timestamp('last_login_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Password Reset Tokens
        |--------------------------------------------------------------------------
        */

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        /*
        |--------------------------------------------------------------------------
        | Sessions Table
        |--------------------------------------------------------------------------
        */

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->foreignId('user_id')
                ->nullable()
                ->index();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
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
//         Schema::create('users', function (Blueprint $table) {
//             $table->id();
//             $table->string('name');
//             $table->string('email')->unique();
//             $table->timestamp('email_verified_at')->nullable();
//             $table->string('password');
//             $table->rememberToken();
//             $table->timestamps();
//         });

//         Schema::create('password_reset_tokens', function (Blueprint $table) {
//             $table->string('email')->primary();
//             $table->string('token');
//             $table->timestamp('created_at')->nullable();
//         });

//         Schema::create('sessions', function (Blueprint $table) {
//             $table->string('id')->primary();
//             $table->foreignId('user_id')->nullable()->index();
//             $table->string('ip_address', 45)->nullable();
//             $table->text('user_agent')->nullable();
//             $table->longText('payload');
//             $table->integer('last_activity')->index();
//         });
//     }

//     /**
//      * Reverse the migrations.
//      */
//     public function down(): void
//     {
//         Schema::dropIfExists('users');
//         Schema::dropIfExists('password_reset_tokens');
//         Schema::dropIfExists('sessions');
//     }
// };