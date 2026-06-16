<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->boolean('verification_services_enabled')->default(true)->after('status');
            $table->boolean('clinic_operations_enabled')->default(true)->after('verification_services_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->dropColumn([
                'verification_services_enabled',
                'clinic_operations_enabled',
            ]);
        });
    }
};
