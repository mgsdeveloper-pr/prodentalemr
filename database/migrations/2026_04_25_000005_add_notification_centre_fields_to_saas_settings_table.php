<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->boolean('notify_database_on_organization_onboarded')->default(true)->after('maintenance_mode');
            $table->boolean('notify_database_on_incomplete_onboarding')->default(true)->after('notify_database_on_organization_onboarded');
            $table->boolean('notify_database_on_settings_updated')->default(true)->after('notify_database_on_incomplete_onboarding');
            $table->boolean('email_enabled')->default(false)->after('notify_database_on_settings_updated');
            $table->string('email_mailer')->default('smtp')->after('email_enabled');
            $table->string('email_host')->nullable()->after('email_mailer');
            $table->unsignedSmallInteger('email_port')->nullable()->after('email_host');
            $table->string('email_username')->nullable()->after('email_port');
            $table->text('email_password')->nullable()->after('email_username');
            $table->string('email_encryption')->nullable()->after('email_password');
            $table->string('email_from_address')->nullable()->after('email_encryption');
            $table->string('email_from_name')->nullable()->after('email_from_address');
            $table->boolean('email_on_organization_onboarded')->default(false)->after('email_from_name');
            $table->boolean('email_on_incomplete_onboarding')->default(false)->after('email_on_organization_onboarded');
            $table->boolean('email_on_settings_updated')->default(false)->after('email_on_incomplete_onboarding');
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'notify_database_on_organization_onboarded',
                'notify_database_on_incomplete_onboarding',
                'notify_database_on_settings_updated',
                'email_enabled',
                'email_mailer',
                'email_host',
                'email_port',
                'email_username',
                'email_password',
                'email_encryption',
                'email_from_address',
                'email_from_name',
                'email_on_organization_onboarded',
                'email_on_incomplete_onboarding',
                'email_on_settings_updated',
            ]);
        });
    }
};
