<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->boolean('billing_automation_enabled')->default(false)->after('paypal_webhook_id');
            $table->boolean('billing_mark_overdue_enabled')->default(true)->after('billing_automation_enabled');
            $table->boolean('billing_send_pre_due_reminders')->default(true)->after('billing_mark_overdue_enabled');
            $table->unsignedTinyInteger('billing_pre_due_days')->default(3)->after('billing_send_pre_due_reminders');
            $table->boolean('billing_send_overdue_reminders')->default(true)->after('billing_pre_due_days');
            $table->unsignedTinyInteger('billing_overdue_reminder_days')->default(1)->after('billing_send_overdue_reminders');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->timestamp('pre_due_reminder_sent_at')->nullable()->after('paypal_order_status');
            $table->timestamp('overdue_reminder_sent_at')->nullable()->after('pre_due_reminder_sent_at');
            $table->timestamp('marked_overdue_at')->nullable()->after('overdue_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'pre_due_reminder_sent_at',
                'overdue_reminder_sent_at',
                'marked_overdue_at',
            ]);
        });

        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'billing_automation_enabled',
                'billing_mark_overdue_enabled',
                'billing_send_pre_due_reminders',
                'billing_pre_due_days',
                'billing_send_overdue_reminders',
                'billing_overdue_reminder_days',
            ]);
        });
    }
};
