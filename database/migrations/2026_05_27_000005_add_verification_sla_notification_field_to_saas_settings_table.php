<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->boolean('verification_notify_on_sla_alert')->default(true)->after('verification_notify_on_urgent_assigned');
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn('verification_notify_on_sla_alert');
        });
    }
};
