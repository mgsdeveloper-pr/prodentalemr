<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_profiles', function (Blueprint $table): void {
            $table->string('location_name')->nullable()->after('appointment_time');
            $table->string('provider_name')->nullable()->after('location_name');
        });
    }

    public function down(): void
    {
        Schema::table('verification_profiles', function (Blueprint $table): void {
            $table->dropColumn(['location_name', 'provider_name']);
        });
    }
};
