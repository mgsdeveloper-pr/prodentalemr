<?php

namespace App\Services\Notifications;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SaasSetting;
use App\Models\Subscription;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProductNotificationService
{
    public function paymentReceived(Payment $payment): void
    {
        $payment->loadMissing(['invoice.organization', 'creator']);
        $invoice = $payment->invoice;

        if (! $invoice) {
            return;
        }

        $this->saasEvent(
            'billing.payment_received',
            'Payment received',
            sprintf(
                'Payment of $%s was recorded for invoice %s.',
                number_format((float) $payment->amount, 2),
                $invoice->invoice_number,
            ),
            'payment_events',
            'success',
            $this->invoiceUrl($invoice),
            'billing.payment.' . ($payment->public_id ?: $payment->getKey()),
            $payment,
            $invoice->organization_id,
        );
    }

    public function invoiceOverdue(Invoice $invoice): void
    {
        $invoice->loadMissing('organization');

        $this->saasEvent(
            'billing.invoice_overdue',
            'Invoice overdue',
            "Invoice {$invoice->invoice_number} is overdue with a balance of $" . number_format((float) $invoice->balance_due, 2) . '.',
            'payment_events',
            'danger',
            $this->invoiceUrl($invoice),
            'billing.invoice.overdue.' . ($invoice->public_id ?: $invoice->getKey()),
            $invoice,
            $invoice->organization_id,
        );
    }

    public function paymentFailed(Invoice $invoice, string $gateway): void
    {
        $invoice->loadMissing('organization');

        $this->saasEvent(
            'billing.payment_failed',
            'Payment failed',
            "A {$gateway} payment attempt failed for invoice {$invoice->invoice_number}.",
            'payment_events',
            'danger',
            $this->invoiceUrl($invoice),
            implode('.', ['billing.payment-failed', $invoice->public_id ?: $invoice->getKey(), Str::slug($gateway), now()->format('YmdHi')]),
            $invoice,
            $invoice->organization_id,
        );
    }

    public function subscriptionChanged(Subscription $subscription, string $change): void
    {
        $subscription->loadMissing(['organization', 'subscriptionPlan']);
        $label = str($change)->replace('_', ' ')->headline()->toString();
        $client = $subscription->organization?->name ?? 'Client account';

        $this->saasEvent(
            'billing.subscription_' . $change,
            'Subscription ' . str($change)->replace('_', ' ')->lower()->toString(),
            "{$client}: {$label}. Current status: {$subscription->status}.",
            'subscription_events',
            in_array($change, ['cancelled', 'expired'], true) ? 'danger' : 'info',
            null,
            implode('.', ['billing.subscription', $subscription->public_id ?: $subscription->getKey(), $change, now()->timestamp]),
            $subscription,
            $subscription->organization_id,
            $subscription->clinic_id,
        );
    }

    public function trialEnding(Subscription $subscription, int $daysRemaining): void
    {
        $subscription->loadMissing('organization');
        $client = $subscription->organization?->name ?? 'Client account';

        $this->saasEvent(
            'billing.trial_ending',
            'Trial ending soon',
            "{$client}'s trial ends in {$daysRemaining} day(s).",
            'subscription_events',
            $daysRemaining <= 1 ? 'danger' : 'warning',
            null,
            implode('.', ['billing.trial', $subscription->public_id ?: $subscription->getKey(), $daysRemaining]),
            $subscription,
            $subscription->organization_id,
            $subscription->clinic_id,
        );
    }

    public function integrationFailure(string $integration, string $summary, ?int $organizationId = null, ?int $clinicId = null, ?string $idempotencyKey = null): void
    {
        $this->saasEvent(
            'integration.' . Str::slug($integration, '_') . '_failed',
            str($integration)->headline()->append(' failed')->toString(),
            $summary,
            'integration_failures',
            'danger',
            null,
            $idempotencyKey ?: 'integration.failure.' . Str::ulid(),
            null,
            $organizationId,
            $clinicId,
        );
    }

    public function supportAccess(string $action, User $actor, array $context): void
    {
        $client = data_get($context, 'clinic_name') ?: data_get($context, 'organization_name', 'client account');
        $verb = $action === 'started' ? 'started' : 'ended';

        $this->saasEvent(
            'security.support_access_' . $action,
            'Support Mode ' . $verb,
            "{$actor->name} {$verb} Support Mode for {$client}.",
            'support_access',
            $action === 'started' ? 'warning' : 'info',
            null,
            implode('.', ['security.support', data_get($context, 'session_id'), $action]),
            null,
            data_get($context, 'organization_id'),
            data_get($context, 'clinic_id'),
        );
    }

    public function accountSecurity(User $affectedUser, string $eventType, string $title, string $message, ?string $idempotencyKey = null): void
    {
        $settings = SaasSetting::current();
        $channels = [];

        if ($settings->notify_database_on_security_alerts) {
            $channels[] = 'in_app';
        }

        if ($settings->email_enabled && $settings->email_on_security_alerts && filled($affectedUser->email)) {
            $channels[] = 'email';
        }

        if ($channels === []) {
            return;
        }

        app(NotificationEngine::class)->publish([
            'event_type' => 'security.' . $eventType,
            'actor_user_id' => $affectedUser->getKey(),
            'organization_id' => $affectedUser->organization_id,
            'clinic_id' => $affectedUser->clinic_id,
            'level' => 'warning',
            'title' => $title,
            'message' => $message,
            'payload' => [
                'external_title' => $title,
                'external_message' => $message . "\n\nIf you did not perform this action, contact your administrator immediately.",
            ],
            'idempotency_key' => $idempotencyKey ?: 'security.' . $eventType . '.' . Str::ulid(),
            'occurred_at' => now(),
        ], [[
            'user' => $affectedUser,
            'panel' => 'account',
            'channels' => $channels,
        ]], function ($event, $delivery, User $recipient) use ($title, $message): void {
            Notification::make()
                ->title($title)
                ->body($message)
                ->warning()
                ->sendToDatabase($recipient, isEventDispatched: true);
        });
    }

    protected function saasEvent(
        string $eventType,
        string $title,
        string $message,
        string $category,
        string $level,
        ?string $targetUrl,
        string $idempotencyKey,
        mixed $subject = null,
        ?int $organizationId = null,
        ?int $clinicId = null,
    ): void {
        $settings = SaasSetting::current();
        $inAppEnabled = (bool) data_get($settings, "notify_database_on_{$category}", true);
        $emailEnabled = (bool) $settings->email_enabled
            && (bool) data_get($settings, "email_on_{$category}", false);

        if (! $inAppEnabled && ! $emailEnabled) {
            return;
        }

        $channels = array_values(array_filter([
            $inAppEnabled ? 'in_app' : null,
            $emailEnabled ? 'email' : null,
        ]));
        $recipients = $this->saasRecipients()
            ->map(fn (User $user): array => [
                'user' => $user,
                'panel' => 'saas',
                'channels' => $channels,
                'target_url' => $targetUrl,
            ])
            ->all();

        app(NotificationEngine::class)->publish([
            'event_type' => $eventType,
            'subject_type' => is_object($subject) && method_exists($subject, 'getMorphClass') ? $subject->getMorphClass() : null,
            'subject_id' => is_object($subject) && method_exists($subject, 'getKey') ? $subject->getKey() : null,
            'actor_user_id' => auth()->id(),
            'organization_id' => $organizationId,
            'clinic_id' => $clinicId,
            'level' => $level,
            'title' => $title,
            'message' => $message,
            'target_url' => $targetUrl,
            'payload' => [
                'external_title' => $title,
                'external_message' => $message . "\n\nSign in to ProDental for details.",
            ],
            'idempotency_key' => $idempotencyKey,
            'occurred_at' => now(),
        ], $recipients, function ($event, $delivery, User $recipient) use ($title, $message, $level, $targetUrl): void {
            $notification = Notification::make()->title($title)->body($message);
            $notification->{$level}();

            if ($targetUrl) {
                $notification->actions([
                    \Filament\Actions\Action::make('open')->label('Open')->url($targetUrl)->markAsRead(),
                ]);
            }

            $notification->sendToDatabase($recipient, isEventDispatched: true);
        });
    }

    protected function saasRecipients(): Collection
    {
        return User::query()
            ->where('status', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', array_keys(User::saasRoleOptions())))
            ->get();
    }

    protected function invoiceUrl(Invoice $invoice): ?string
    {
        try {
            return \App\Filament\Saas\Resources\Invoices\InvoiceResource::getUrl('view', ['record' => $invoice]);
        } catch (\Throwable) {
            return null;
        }
    }
}
