<?php

namespace App\Services\Notifications;

use App\Models\NotificationDelivery;
use App\Models\SaasSetting;
use App\Support\SaasMailSettings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class NotificationDeliveryProcessor
{
    public function process(NotificationDelivery $delivery): void
    {
        if (in_array($delivery->status, [NotificationDelivery::STATUS_DELIVERED, NotificationDelivery::STATUS_SKIPPED], true)) {
            return;
        }

        $delivery->loadMissing(['event', 'recipient']);
        $delivery->forceFill([
            'status' => NotificationDelivery::STATUS_PROCESSING,
            'attempts' => $delivery->attempts + 1,
        ])->save();

        try {
            match ($delivery->channel) {
                'email' => $this->sendEmail($delivery),
                default => throw new RuntimeException("Unsupported notification channel [{$delivery->channel}]."),
            };

            if ($delivery->fresh()->status === NotificationDelivery::STATUS_SKIPPED) {
                return;
            }

            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_DELIVERED,
                'delivered_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'last_error' => Str::limit($exception->getMessage(), 2000),
            ])->save();

            throw $exception;
        }
    }

    protected function sendEmail(NotificationDelivery $delivery): void
    {
        $event = $delivery->event;
        $settings = SaasSetting::current();
        $state = $settings->toArray();

        if (! SaasMailSettings::canSend($state)) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SKIPPED,
                'last_error' => 'Email delivery is not configured.',
            ])->save();

            return;
        }

        if (blank($delivery->destination)) {
            throw new RuntimeException('Notification recipient does not have an email address.');
        }

        SaasMailSettings::apply($state);

        $subject = data_get($event->payload, 'external_title', $event->title);
        $message = data_get(
            $event->payload,
            'external_message',
            'A ProDental notification requires your attention. Sign in to review the details securely.',
        );

        Mail::mailer($state['email_mailer'] ?? 'smtp')->raw($message, function ($mail) use ($delivery, $subject): void {
            $mail->to($delivery->destination)->subject($subject);
        });
    }
}
