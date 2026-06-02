<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->boolean('notify_database_on_invoice_created')->default(true)->after('notify_database_on_user_deleted');
            $table->boolean('notify_database_on_invoice_updated')->default(true)->after('notify_database_on_invoice_created');
            $table->boolean('notify_database_on_invoice_deleted')->default(true)->after('notify_database_on_invoice_updated');
            $table->boolean('notify_database_on_invoice_sent')->default(true)->after('notify_database_on_invoice_deleted');
            $table->boolean('email_on_invoice_created')->default(false)->after('email_send_user_verification');
            $table->boolean('email_on_invoice_updated')->default(false)->after('email_on_invoice_created');
            $table->boolean('email_on_invoice_deleted')->default(false)->after('email_on_invoice_updated');
            $table->boolean('email_on_invoice_sent')->default(true)->after('email_on_invoice_deleted');
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'notify_database_on_invoice_created',
                'notify_database_on_invoice_updated',
                'notify_database_on_invoice_deleted',
                'notify_database_on_invoice_sent',
                'email_on_invoice_created',
                'email_on_invoice_updated',
                'email_on_invoice_deleted',
                'email_on_invoice_sent',
            ]);
        });
    }
};
