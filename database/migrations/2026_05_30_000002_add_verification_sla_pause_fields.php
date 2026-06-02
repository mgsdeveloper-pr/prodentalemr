<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_work_items', function (Blueprint $table): void {
            $table->dateTime('sla_pause_started_at')->nullable()->after('clinic_responded_at');
            $table->unsignedBigInteger('sla_paused_seconds')->default(0)->after('sla_pause_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('billing_work_items', function (Blueprint $table): void {
            $table->dropColumn(['sla_pause_started_at', 'sla_paused_seconds']);
        });
    }
};
