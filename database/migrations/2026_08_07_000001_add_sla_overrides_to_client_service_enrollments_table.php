<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_service_enrollments', function (Blueprint $table): void {
            if (! Schema::hasColumn('client_service_enrollments', 'normal_sla_days')) {
                $table->unsignedInteger('normal_sla_days')
                    ->default(3)
                    ->after('clinic_workspace_enabled');
            }

            if (! Schema::hasColumn('client_service_enrollments', 'urgent_sla_hours')) {
                $table->unsignedInteger('urgent_sla_hours')
                    ->default(24)
                    ->after('normal_sla_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_service_enrollments', function (Blueprint $table): void {
            if (Schema::hasColumn('client_service_enrollments', 'urgent_sla_hours')) {
                $table->dropColumn('urgent_sla_hours');
            }

            if (Schema::hasColumn('client_service_enrollments', 'normal_sla_days')) {
                $table->dropColumn('normal_sla_days');
            }
        });
    }
};
