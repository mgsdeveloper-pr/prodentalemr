<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->boolean('verification_round_robin_enabled')
                ->default(false)
                ->after('verification_notify_on_sla_alert');
            $table->unsignedBigInteger('verification_round_robin_last_user_id')
                ->nullable()
                ->after('verification_round_robin_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'verification_round_robin_enabled',
                'verification_round_robin_last_user_id',
            ]);
        });
    }
};
