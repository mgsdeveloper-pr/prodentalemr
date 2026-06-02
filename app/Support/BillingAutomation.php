<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\SaasSetting;

class BillingAutomation
{
    public function run(): array
    {
        $settings = SaasSetting::current();

        if (! $settings->billing_automation_enabled) {
            return [
                'overdue_marked' => 0,
                'pre_due_sent' => 0,
                'overdue_sent' => 0,
                'skipped' => true,
            ];
        }

        return [
            'overdue_marked' => $this->markOverdueInvoices($settings),
            'pre_due_sent' => $this->sendPreDueReminders($settings),
            'overdue_sent' => $this->sendOverdueReminders($settings),
            'skipped' => false,
        ];
    }

    public function markOverdueInvoices(?SaasSetting $settings = null): int
    {
        $settings ??= SaasSetting::current();

        if (! $settings->billing_mark_overdue_enabled) {
            return 0;
        }

        $count = 0;

        Invoice::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', ['paid', 'cancelled', 'overdue'])
            ->each(function (Invoice $invoice) use (&$count): void {
                $invoice->forceFill([
                    'status' => 'overdue',
                    'marked_overdue_at' => now(),
                ])->save();

                $count++;
            });

        return $count;
    }

    public function sendPreDueReminders(?SaasSetting $settings = null): int
    {
        $settings ??= SaasSetting::current();

        if (! $settings->billing_send_pre_due_reminders) {
            return 0;
        }

        $targetDate = today()->addDays((int) ($settings->billing_pre_due_days ?: 3));
        $count = 0;

        Invoice::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', $targetDate)
            ->where('balance_due', '>', 0)
            ->whereIn('status', ['sent', 'partial'])
            ->whereNull('pre_due_reminder_sent_at')
            ->with('organization')
            ->each(function (Invoice $invoice) use (&$count): void {
                if (! SaasNotifications::sendAutomatedInvoiceReminder($invoice, 'upcoming due date')) {
                    return;
                }

                $invoice->forceFill([
                    'pre_due_reminder_sent_at' => now(),
                ])->save();

                $count++;
            });

        return $count;
    }

    public function sendOverdueReminders(?SaasSetting $settings = null): int
    {
        $settings ??= SaasSetting::current();

        if (! $settings->billing_send_overdue_reminders) {
            return 0;
        }

        $targetDate = today()->subDays((int) ($settings->billing_overdue_reminder_days ?: 1));
        $count = 0;

        Invoice::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $targetDate)
            ->where('balance_due', '>', 0)
            ->where('status', 'overdue')
            ->whereNull('overdue_reminder_sent_at')
            ->with('organization')
            ->each(function (Invoice $invoice) use (&$count): void {
                if (! SaasNotifications::sendAutomatedInvoiceReminder($invoice, 'overdue payment')) {
                    return;
                }

                $invoice->forceFill([
                    'overdue_reminder_sent_at' => now(),
                ])->save();

                $count++;
            });

        return $count;
    }
}
