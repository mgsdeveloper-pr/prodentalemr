<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\SaasSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Spatie\Permission\Models\Role;

class ModuleWarnings
{
    public static function for(string $module): array
    {
        $settings = SaasSetting::current();
        $warnings = [];

        switch ($module) {
            case 'settings':
                if (blank($settings->support_email)) {
                    $warnings[] = 'Support email is missing in SaaS Settings.';
                }
                if (blank($settings->company_name)) {
                    $warnings[] = 'Company name is missing in SaaS Settings.';
                }
                if (! $settings->billing_automation_enabled) {
                    $warnings[] = 'Billing automation is disabled. Overdue updates and automated reminders will not run.';
                }
                break;

            case 'notification-centre':
                if (! $settings->email_enabled) {
                    $warnings[] = 'Email notifications are disabled. Verification, reminders, and invoice emails will not send.';
                } elseif (! SaasMailSettings::canSend($settings->toArray())) {
                    $warnings[] = 'Email delivery is enabled, but the mail configuration is incomplete.';
                }
                break;

            case 'payment-credentials':
                if (! $settings->stripe_enabled && ! $settings->paypal_enabled) {
                    $warnings[] = 'No payment gateway is enabled. Online invoice payment links will not work.';
                }
                if ($settings->stripe_enabled && blank($settings->stripe_webhook_secret)) {
                    $warnings[] = 'Stripe is enabled, but the Stripe webhook secret is missing.';
                }
                if ($settings->paypal_enabled && blank($settings->paypal_webhook_id)) {
                    $warnings[] = 'PayPal is enabled, but the PayPal webhook ID is missing.';
                }
                break;

            case 'billing-reports':
                if (! Invoice::query()->exists()) {
                    $warnings[] = 'No invoices exist yet, so billing reports will stay empty.';
                }
                if (! Payment::query()->exists()) {
                    $warnings[] = 'No payments have been recorded yet, so collections reporting is limited.';
                }
                break;

            case 'tenant-onboarding':
                if (! SubscriptionPlan::query()->where('status', true)->exists()) {
                    $warnings[] = 'No active subscription plans exist. Organization onboarding can continue, but billing cannot be attached.';
                }
                break;

            case 'organizations':
                if (! Organization::query()->exists()) {
                    $warnings[] = 'No organizations exist yet. Start with Organization Onboarding.';
                }
                break;

            case 'clinics':
                if (! Organization::query()->exists()) {
                    $warnings[] = 'No organizations exist yet. Create an organization before creating clinics.';
                }
                break;

            case 'locations':
                if (! Clinic::query()->exists()) {
                    $warnings[] = 'No clinics exist yet. Create a clinic before adding locations.';
                }
                break;

            case 'users':
                if (! Role::query()->whereIn('name', array_keys(User::saasRoleOptions()))->exists()) {
                    $warnings[] = 'SaaS roles are missing. User management may not behave correctly until roles are seeded.';
                }
                if (! User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', array_keys(User::saasRoleOptions())))->exists()) {
                    $warnings[] = 'No SaaS users exist yet.';
                }
                break;

            case 'subscription-plans':
                if (! SubscriptionPlan::query()->exists()) {
                    $warnings[] = 'No subscription plans exist yet.';
                }
                break;

            case 'subscriptions':
                if (! Organization::query()->exists()) {
                    $warnings[] = 'No organizations exist yet. Create an organization before attaching subscriptions.';
                }
                if (! SubscriptionPlan::query()->where('status', true)->exists()) {
                    $warnings[] = 'No active subscription plans exist. Subscriptions cannot be created until at least one active plan exists.';
                }
                break;

            case 'invoices':
                if (! Organization::query()->exists()) {
                    $warnings[] = 'No organizations exist yet. Create an organization before issuing invoices.';
                }
                if (! $settings->email_enabled) {
                    $warnings[] = 'Email notifications are disabled. Invoice send and reminder actions will not deliver email.';
                }
                if (! $settings->stripe_enabled && ! $settings->paypal_enabled) {
                    $warnings[] = 'No payment gateway is enabled. Invoices can be created, but online payment links will not be available.';
                }
                break;

            case 'payments':
                if (! Invoice::query()->exists()) {
                    $warnings[] = 'No invoices exist yet. Payments should normally be attached to invoices.';
                }
                if (! Payment::query()->exists()) {
                    $warnings[] = 'No payments have been recorded yet.';
                }
                break;
        }

        return $warnings;
    }

    public static function actionFor(string $module): ?array
    {
        return match ($module) {
            'settings' => ['label' => 'Open SaaS Settings', 'url' => url('/saas/settings')],
            'notification-centre' => ['label' => 'Open Notification Centre', 'url' => url('/saas/notification-centre')],
            'payment-credentials' => ['label' => 'Open Payment Credentials', 'url' => url('/saas/payment-credentials')],
            'billing-reports' => ['label' => 'Open Billing Reports', 'url' => url('/saas/billing-reports')],
            'tenant-onboarding' => ['label' => 'Open Organization Onboarding', 'url' => url('/saas/organization-onboarding')],
            'organizations' => ['label' => 'Open Organizations', 'url' => url('/saas/organizations')],
            'clinics' => ['label' => 'Open Clinics', 'url' => url('/saas/clinics')],
            'locations' => ['label' => 'Open Locations', 'url' => url('/saas/locations')],
            'users' => ['label' => 'Open Users', 'url' => url('/saas/users')],
            'subscription-plans' => ['label' => 'Open Subscription Plans', 'url' => url('/saas/subscription-plans')],
            'subscriptions' => ['label' => 'Open Subscriptions', 'url' => url('/saas/subscriptions')],
            'invoices' => ['label' => 'Open Invoices', 'url' => url('/saas/invoices')],
            'payments' => ['label' => 'Open Payments', 'url' => url('/saas/payments')],
            default => null,
        };
    }

    public static function recordChecks(): array
    {
        $checks = [];

        $invoiceEmailIssueQuery = Invoice::query()
            ->with('organization:id,name,email')
            ->whereHas('organization', fn ($query) => $query->whereNull('email')->orWhere('email', ''));
        $invoiceEmailIssueCount = (clone $invoiceEmailIssueQuery)->count();
        $invoiceEmailIssues = $invoiceEmailIssueQuery->latest('id')->limit(5)->get();

        if ($invoiceEmailIssues->isNotEmpty()) {
            $checks[] = [
                'label' => 'Invoices missing billing email',
                'count' => $invoiceEmailIssueCount,
                'description' => 'These invoices cannot be emailed until the linked organization has a billing email address.',
                'action' => ['label' => 'Review Invoices', 'url' => url('/saas/invoices')],
                'items' => $invoiceEmailIssues
                    ->map(function (Invoice $invoice): array {
                        return [
                            'title' => $invoice->invoice_number,
                            'message' => 'Organization "' . ($invoice->organization?->name ?? 'Unknown') . '" does not have an email address.',
                        ];
                    })
                    ->all(),
            ];
        }

        $subscriptionPlanIssueQuery = Subscription::query()
            ->with(['organization:id,name', 'subscriptionPlan:id,name,status'])
            ->where(function ($query): void {
                $query->whereDoesntHave('subscriptionPlan')
                    ->orWhereHas('subscriptionPlan', fn ($planQuery) => $planQuery->where('status', false));
            });
        $subscriptionPlanIssueCount = (clone $subscriptionPlanIssueQuery)->count();
        $subscriptionPlanIssues = $subscriptionPlanIssueQuery->latest('id')->limit(5)->get();

        if ($subscriptionPlanIssues->isNotEmpty()) {
            $checks[] = [
                'label' => 'Subscriptions linked to inactive plans',
                'count' => $subscriptionPlanIssueCount,
                'description' => 'These subscriptions should be reviewed before billing or renewals continue.',
                'action' => ['label' => 'Review Subscriptions', 'url' => url('/saas/subscriptions')],
                'items' => $subscriptionPlanIssues
                    ->map(function (Subscription $subscription): array {
                        $planName = $subscription->subscriptionPlan?->name ?? 'Missing plan';

                        return [
                            'title' => $subscription->organization?->name ?? 'Unknown organization',
                            'message' => 'Subscription references "' . $planName . '", which is inactive or missing.',
                        ];
                    })
                    ->all(),
            ];
        }

        $paymentMismatchInvoices = Invoice::query()
            ->with('organization:id,name')
            ->withSum('payments', 'amount')
            ->latest('id')
            ->get()
            ->filter(function (Invoice $invoice): bool {
                $paymentTotal = (float) ($invoice->payments_sum_amount ?? 0);
                $amountPaid = (float) $invoice->amount_paid;
                $balanceDue = (float) $invoice->balance_due;
                $expectedBalance = max((float) $invoice->total_amount - $paymentTotal, 0);

                return abs($paymentTotal - $amountPaid) > 0.01
                    || abs($expectedBalance - $balanceDue) > 0.01;
            })
            ->values();
        $paymentMismatchInvoiceCount = $paymentMismatchInvoices->count();
        $paymentMismatchInvoices = $paymentMismatchInvoices->take(5);

        if ($paymentMismatchInvoices->isNotEmpty()) {
            $checks[] = [
                'label' => 'Invoices with payment summary mismatch',
                'count' => $paymentMismatchInvoiceCount,
                'description' => 'These invoices should be reviewed because stored totals do not match the underlying payment records.',
                'action' => ['label' => 'Review Payments', 'url' => url('/saas/payments')],
                'items' => $paymentMismatchInvoices
                    ->map(function (Invoice $invoice): array {
                        $paymentTotal = number_format((float) ($invoice->payments_sum_amount ?? 0), 2);
                        $recordedPaid = number_format((float) $invoice->amount_paid, 2);
                        $balanceDue = number_format((float) $invoice->balance_due, 2);

                        return [
                            'title' => $invoice->invoice_number,
                            'message' => 'Payments total ' . $paymentTotal . ' but recorded paid amount is ' . $recordedPaid . ' and balance due is ' . $balanceDue . '.',
                        ];
                    })
                    ->all(),
            ];
        }

        return $checks;
    }
}
