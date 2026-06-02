<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->boolean('notify_database_on_user_created')->default(true)->after('notify_database_on_settings_updated');
            $table->boolean('notify_database_on_user_updated')->default(true)->after('notify_database_on_user_created');
            $table->boolean('notify_database_on_user_deleted')->default(true)->after('notify_database_on_user_updated');
            $table->boolean('email_on_user_created')->default(false)->after('email_on_settings_updated');
            $table->boolean('email_on_user_updated')->default(false)->after('email_on_user_created');
            $table->boolean('email_on_user_deleted')->default(false)->after('email_on_user_updated');
            $table->boolean('email_send_user_verification')->default(true)->after('email_on_user_deleted');
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'notify_database_on_user_created',
                'notify_database_on_user_updated',
                'notify_database_on_user_deleted',
                'email_on_user_created',
                'email_on_user_updated',
                'email_on_user_deleted',
                'email_send_user_verification',
            ]);
        });
    }
};
