<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_service_enrollments', function (Blueprint $table): void {
            $table->boolean('clinic_workspace_enabled')
                ->default(true)
                ->after('status');
        });

        DB::table('client_service_enrollments')
            ->whereNull('deleted_at')
            ->update(['clinic_workspace_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('client_service_enrollments', function (Blueprint $table): void {
            $table->dropColumn('clinic_workspace_enabled');
        });
    }
};
