<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->boolean('notify_database_on_security_alerts')->default(true);
            $table->boolean('email_on_security_alerts')->default(true);
            $table->boolean('notify_database_on_payment_events')->default(true);
            $table->boolean('email_on_payment_events')->default(true);
            $table->boolean('notify_database_on_subscription_events')->default(true);
            $table->boolean('email_on_subscription_events')->default(true);
            $table->boolean('notify_database_on_integration_failures')->default(true);
            $table->boolean('email_on_integration_failures')->default(true);
            $table->boolean('notify_database_on_support_access')->default(true);
            $table->boolean('email_on_support_access')->default(false);
            $table->boolean('verification_email_on_audit')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'notify_database_on_security_alerts',
                'email_on_security_alerts',
                'notify_database_on_payment_events',
                'email_on_payment_events',
                'notify_database_on_subscription_events',
                'email_on_subscription_events',
                'notify_database_on_integration_failures',
                'email_on_integration_failures',
                'notify_database_on_support_access',
                'email_on_support_access',
                'verification_email_on_audit',
            ]);
        });
    }
};
