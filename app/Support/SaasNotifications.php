<?php

namespace App\Support;

use App\Filament\Saas\Resources\Invoices\InvoiceResource;
use App\Filament\Saas\Resources\Organizations\OrganizationResource;
use App\Models\Invoice;
use App\Models\OnboardingDraft;
use App\Models\Organization;
use App\Models\SaasSetting;
use App\Models\User;
use App\Support\InvoicePdf;
use App\Support\PayPalGateway;
use App\Support\StripeGateway;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;

class SaasNotifications
{
    protected static function recipients(): Collection
    {
        return User::query()
            ->where('status', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', array_keys(User::saasRoleOptions())))
            ->get();
    }

    protected static function settings(): SaasSetting
    {
        return SaasSetting::current();
    }

    protected static function sendDatabaseNotification(Notification $notification, string $settingKey): void
    {
        if (! (static::settings()->{$settingKey} ?? false)) {
            return;
        }

        $notification->sendToDatabase(static::recipients(), isEventDispatched: true);
    }

    protected static function sendEmailNotification(string $settingKey, string $subject, string $body): void
    {
        $settings = static::settings();

        if (! ($settings->{$settingKey} ?? false)) {
            return;
        }

        $state = $settings->toArray();

        if (! SaasMailSettings::canSend($state)) {
            return;
        }

        SaasMailSettings::apply($state);

        $recipients = static::recipients()
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($recipients as $recipient) {
            Mail::mailer($state['email_mailer'] ?? 'smtp')
                ->raw($body, function ($message) use ($recipient, $subject): void {
                    $message->to($recipient)->subject($subject);
                });
        }
    }

    protected static function sendDirectEmail(string $recipient, string $subject, string $body, ?array $attachment = null): void
    {
        $state = static::settings()->toArray();

        if (! SaasMailSettings::canSend($state)) {
            return;
        }

        SaasMailSettings::apply($state);

        Mail::mailer($state['email_mailer'] ?? 'smtp')
            ->raw($body, function ($message) use ($attachment, $recipient, $subject): void {
                $message->to($recipient)->subject($subject);

                if ($attachment) {
                    $message->attachData(
                        $attachment['data'],
                        $attachment['name'],
                        ['mime' => $attachment['mime']],
                    );
                }
            });
    }

    public static function userCreated(User $user, ?User $actor = null): void
    {
        $role = $user->getPrimaryRoleLabel() ?? 'User';
        $message = $actor
            ? "{$actor->name} created {$user->name} ({$user->email}) with {$role} access."
            : "{$user->name} ({$user->email}) was created with {$role} access.";

        static::sendDatabaseNotification(
            Notification::make()
                ->title('User created')
                ->body($message)
                ->success(),
            'notify_database_on_user_created',
        );

        static::sendEmailNotification(
            'email_on_user_created',
            'User created',
            $message,
        );
    }

    public static function userUpdated(User $user, ?User $actor = null): void
    {
        $role = $user->getPrimaryRoleLabel() ?? 'User';
        $message = $actor
            ? "{$actor->name} updated {$user->name} ({$user->email}). Current role: {$role}."
            : "{$user->name} ({$user->email}) was updated. Current role: {$role}.";

        static::sendDatabaseNotification(
            Notification::make()
                ->title('User updated')
                ->body($message)
                ->info(),
            'notify_database_on_user_updated',
        );

        static::sendEmailNotification(
            'email_on_user_updated',
            'User updated',
            $message,
        );
    }

    public static function userDeleted(string $name, string $email, ?User $actor = null): void
    {
        $message = $actor
            ? "{$actor->name} deleted {$name} ({$email})."
            : "{$name} ({$email}) was deleted.";

        static::sendDatabaseNotification(
            Notification::make()
                ->title('User deleted')
                ->body($message)
                ->danger(),
            'notify_database_on_user_deleted',
        );

        static::sendEmailNotification(
            'email_on_user_deleted',
            'User deleted',
            $message,
        );
    }

    public static function sendUserVerificationEmail(User $user): void
    {
        $settings = static::settings();

        if (! ($settings->email_send_user_verification ?? false)) {
            return;
        }

        $state = $settings->toArray();

        if (! SaasMailSettings::canSend($state)) {
            return;
        }

        SaasMailSettings::apply($state);
        $user->sendEmailVerificationNotification();
    }

    public static function organizationOnboarded(Organization $organization, User $owner): void
    {
        $message = "{$organization->name} has been onboarded. Owner login: {$owner->email}.";

        static::sendDatabaseNotification(
            Notification::make()
            ->title('New organization onboarded')
            ->body($message)
            ->success()
            ->actions([
                Action::make('viewOrganization')
                    ->label('View organization')
                    ->url(OrganizationResource::getUrl('view', ['record' => $organization]), shouldOpenInNewTab: false)
                    ->markAsRead(),
            ]),
            'notify_database_on_organization_onboarded',
        );

        static::sendEmailNotification(
            'email_on_organization_onboarded',
            'New organization onboarded',
            $message,
        );
    }

    public static function incompleteOnboarding(OnboardingDraft $draft): void
    {
        $actionName = "resumeOnboardingDraft{$draft->id}";

        static::sendDatabaseNotification(
            Notification::make()
            ->title('Incomplete organization onboarding')
            ->body('An onboarding draft is waiting to be completed. Resume from where you left off.')
            ->warning()
            ->actions([
                Action::make($actionName)
                    ->label('Resume')
                    ->url(url('/saas/organization-onboarding'), shouldOpenInNewTab: false)
                    ->markAsRead(),
            ]),
            'notify_database_on_incomplete_onboarding',
        );

        static::sendEmailNotification(
            'email_on_incomplete_onboarding',
            'Incomplete organization onboarding',
            'An organization onboarding draft is waiting to be completed. Resume it from the SaaS panel.',
        );

        $draft->forceFill([
            'notification_sent_at' => now(),
        ])->save();
    }

    public static function clearIncompleteOnboarding(OnboardingDraft $draft): void
    {
        DatabaseNotification::query()
            ->where('type', \Filament\Notifications\DatabaseNotification::class)
            ->where('data', 'like', '%Incomplete organization onboarding%')
            ->where('data', 'like', "%resumeOnboardingDraft{$draft->id}%")
            ->delete();
    }

    public static function settingsUpdated(?User $actor = null): void
    {
        $platformName = SaasSetting::current()->brandName();
        $message = $actor
            ? "{$actor->name} updated the SaaS settings for {$platformName}."
            : "SaaS settings were updated for {$platformName}.";

        static::sendDatabaseNotification(
            Notification::make()
            ->title('SaaS settings updated')
            ->body($message)
            ->info(),
            'notify_database_on_settings_updated',
        );

        static::sendEmailNotification(
            'email_on_settings_updated',
            'SaaS settings updated',
            $message,
        );
    }

    public static function invoiceCreated(Invoice $invoice, ?User $actor = null): void
    {
        $invoice->loadMissing('organization');

        $message = $actor
            ? "{$actor->name} created invoice {$invoice->invoice_number} for {$invoice->organization?->name}. Total: $" . number_format((float) $invoice->total_amount, 2) . '.'
            : "Invoice {$invoice->invoice_number} was created for {$invoice->organization?->name}. Total: $" . number_format((float) $invoice->total_amount, 2) . '.';

        static::sendDatabaseNotification(
            Notification::make()
                ->title('Invoice created')
                ->body($message)
                ->success()
                ->actions([
                    Action::make("viewInvoice{$invoice->id}")
                        ->label('View invoice')
                        ->url(InvoiceResource::getUrl('view', ['record' => $invoice]), shouldOpenInNewTab: false)
                        ->markAsRead(),
                ]),
            'notify_database_on_invoice_created',
        );

        static::sendEmailNotification(
            'email_on_invoice_created',
            'Invoice created',
            $message,
        );
    }

    public static function invoiceUpdated(Invoice $invoice, ?User $actor = null): void
    {
        $invoice->loadMissing('organization');

        $message = $actor
            ? "{$actor->name} updated invoice {$invoice->invoice_number} for {$invoice->organization?->name}. Status: {$invoice->status}."
            : "Invoice {$invoice->invoice_number} for {$invoice->organization?->name} was updated. Status: {$invoice->status}.";

        static::sendDatabaseNotification(
            Notification::make()
                ->title('Invoice updated')
                ->body($message)
                ->info()
                ->actions([
                    Action::make("viewUpdatedInvoice{$invoice->id}")
                        ->label('View invoice')
                        ->url(InvoiceResource::getUrl('view', ['record' => $invoice]), shouldOpenInNewTab: false)
                        ->markAsRead(),
                ]),
            'notify_database_on_invoice_updated',
        );

        static::sendEmailNotification(
            'email_on_invoice_updated',
            'Invoice updated',
            $message,
        );
    }

    public static function invoiceDeleted(string $invoiceNumber, ?string $organizationName = null, ?User $actor = null): void
    {
        $target = $organizationName ? "{$invoiceNumber} for {$organizationName}" : $invoiceNumber;
        $message = $actor
            ? "{$actor->name} deleted invoice {$target}."
            : "Invoice {$target} was deleted.";

        static::sendDatabaseNotification(
            Notification::make()
                ->title('Invoice deleted')
                ->body($message)
                ->danger(),
            'notify_database_on_invoice_deleted',
        );

        static::sendEmailNotification(
            'email_on_invoice_deleted',
            'Invoice deleted',
            $message,
        );
    }

    public static function sendInvoiceEmail(Invoice $invoice, ?User $actor = null): bool
    {
        $settings = static::settings();
        $invoice->loadMissing(['organization', 'items', 'subscription.subscriptionPlan']);

        $recipient = $invoice->organization?->email;

        if (blank($recipient)) {
            return false;
        }

        $state = $settings->toArray();

        if (! SaasMailSettings::canSend($state)) {
            return false;
        }

        $itemLines = $invoice->items
            ->map(fn ($item): string => "- {$item->description}: {$item->quantity} x $" . number_format((float) $item->unit_price, 2) . ' = $' . number_format((float) $item->line_total, 2))
            ->implode("\n");

        $body = implode("\n", array_filter([
            "Invoice Number: {$invoice->invoice_number}",
            "Organization: {$invoice->organization?->name}",
            $invoice->subscription?->subscriptionPlan?->name ? "Plan: {$invoice->subscription->subscriptionPlan->name}" : null,
            'Issue Date: ' . optional($invoice->issue_date)?->format('Y-m-d'),
            $invoice->due_date ? 'Due Date: ' . $invoice->due_date->format('Y-m-d') : null,
            "Status: {$invoice->status}",
            '',
            'Invoice Items:',
            $itemLines ?: '- No line items recorded',
            '',
            'Subtotal: $' . number_format((float) $invoice->subtotal, 2),
            'Tax: $' . number_format((float) $invoice->tax_amount, 2),
            'Discount: $' . number_format((float) $invoice->discount_amount, 2),
            'Total: $' . number_format((float) $invoice->total_amount, 2),
            'Paid: $' . number_format((float) $invoice->amount_paid, 2),
            'Balance Due: $' . number_format((float) $invoice->balance_due, 2),
            StripeGateway::canCreatePaymentLinks() && $invoice->balance_due > 0 ? '' : null,
            StripeGateway::canCreatePaymentLinks() && $invoice->balance_due > 0 ? 'Pay online with Stripe: ' . StripeGateway::paymentPageUrl($invoice) : null,
            PayPalGateway::canCreatePaymentLinks() && $invoice->balance_due > 0 ? 'Pay online with PayPal: ' . PayPalGateway::paymentPageUrl($invoice) : null,
            $invoice->notes ? '' : null,
            $invoice->notes ? "Notes: {$invoice->notes}" : null,
        ]));

        static::sendDirectEmail(
            $recipient,
            "Invoice {$invoice->invoice_number} from " . static::settings()->brandName(),
            $body,
            [
                'data' => InvoicePdf::output($invoice),
                'name' => InvoicePdf::fileName($invoice),
                'mime' => 'application/pdf',
            ],
        );

        if ($invoice->status === 'draft') {
            $invoice->status = 'sent';
            $invoice->save();
        }

        $message = $actor
            ? "{$actor->name} sent invoice {$invoice->invoice_number} to {$recipient}."
            : "Invoice {$invoice->invoice_number} was sent to {$recipient}.";

        static::sendDatabaseNotification(
            Notification::make()
                ->title('Invoice sent')
                ->body($message)
                ->success()
                ->actions([
                    Action::make("viewSentInvoice{$invoice->id}")
                        ->label('View invoice')
                        ->url(InvoiceResource::getUrl('view', ['record' => $invoice]), shouldOpenInNewTab: false)
                        ->markAsRead(),
                ]),
            'notify_database_on_invoice_sent',
        );

        static::sendEmailNotification(
            'email_on_invoice_sent',
            'Invoice sent',
            $message,
        );

        return true;
    }

    public static function sendInvoicePaymentReminder(Invoice $invoice, ?User $actor = null): bool
    {
        $invoice->loadMissing('organization');

        $recipient = $invoice->organization?->email;
        $state = static::settings()->toArray();

        if (
            blank($recipient)
            || (! StripeGateway::canCreatePaymentLinks() && ! PayPalGateway::canCreatePaymentLinks())
            || ! SaasMailSettings::canSend($state)
        ) {
            return false;
        }

        $body = implode("\n", [
            "This is a reminder that invoice {$invoice->invoice_number} is still awaiting payment.",
            'Organization: ' . $invoice->organization?->name,
            'Balance Due: $' . number_format((float) $invoice->balance_due, 2),
            'Due Date: ' . optional($invoice->due_date)?->format('Y-m-d'),
            '',
            'Complete payment securely here:',
            ...array_values(array_filter([
                StripeGateway::canCreatePaymentLinks() ? 'Stripe: ' . StripeGateway::paymentPageUrl($invoice) : null,
                PayPalGateway::canCreatePaymentLinks() ? 'PayPal: ' . PayPalGateway::paymentPageUrl($invoice) : null,
            ])),
        ]);

        static::sendDirectEmail(
            $recipient,
            "Payment reminder for invoice {$invoice->invoice_number}",
            $body,
        );

        static::sendDatabaseNotification(
            Notification::make()
                ->title('Invoice reminder sent')
                ->body(($actor?->name ? "{$actor->name} sent a payment reminder for " : 'A payment reminder was sent for ') . "{$invoice->invoice_number}.")
                ->success()
                ->actions([
                    Action::make("viewReminderInvoice{$invoice->id}")
                        ->label('View invoice')
                        ->url(InvoiceResource::getUrl('view', ['record' => $invoice]), shouldOpenInNewTab: false)
                        ->markAsRead(),
                ]),
            'notify_database_on_invoice_sent',
        );

        return true;
    }

    public static function sendAutomatedInvoiceReminder(Invoice $invoice, string $context): bool
    {
        $invoice->loadMissing('organization');

        $recipient = $invoice->organization?->email;
        $state = static::settings()->toArray();

        if (
            blank($recipient)
            || (! StripeGateway::canCreatePaymentLinks() && ! PayPalGateway::canCreatePaymentLinks())
            || ! SaasMailSettings::canSend($state)
        ) {
            return false;
        }

        $subject = "Invoice {$invoice->invoice_number} {$context}";
        $body = implode("\n", [
            "This is an automated reminder for invoice {$invoice->invoice_number}.",
            'Organization: ' . $invoice->organization?->name,
            'Context: ' . ucfirst($context),
            'Balance Due: $' . number_format((float) $invoice->balance_due, 2),
            'Due Date: ' . optional($invoice->due_date)?->format('Y-m-d'),
            '',
            'Complete payment securely here:',
            ...array_values(array_filter([
                StripeGateway::canCreatePaymentLinks() ? 'Stripe: ' . StripeGateway::paymentPageUrl($invoice) : null,
                PayPalGateway::canCreatePaymentLinks() ? 'PayPal: ' . PayPalGateway::paymentPageUrl($invoice) : null,
            ])),
        ]);

        static::sendDirectEmail($recipient, $subject, $body);

        static::sendDatabaseNotification(
            Notification::make()
                ->title('Automated invoice reminder sent')
                ->body("Invoice {$invoice->invoice_number} received an automated {$context} reminder.")
                ->info()
                ->actions([
                    Action::make("viewAutomatedReminderInvoice{$invoice->id}")
                        ->label('View invoice')
                        ->url(InvoiceResource::getUrl('view', ['record' => $invoice]), shouldOpenInNewTab: false)
                        ->markAsRead(),
                ]),
            'notify_database_on_invoice_sent',
        );

        return true;
    }
}
