<?php

namespace App\Services\Notifications;

use App\Jobs\DispatchNotificationDelivery;
use App\Models\NotificationDelivery;
use App\Models\NotificationEvent;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class NotificationEngine
{
    /**
     * @param  iterable<array{user: User, panel?: string|null, channels?: array<int, string>, target_url?: string|null}>  $recipients
     * @param  Closure(NotificationEvent, NotificationDelivery, User, array): void|null  $inAppWriter
     */
    public function publish(array $eventData, iterable $recipients, ?Closure $inAppWriter = null): NotificationEvent
    {
        return DB::transaction(function () use ($eventData, $recipients, $inAppWriter): NotificationEvent {
            $idempotencyKey = (string) ($eventData['idempotency_key'] ?? Str::ulid());

            $event = NotificationEvent::query()->firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'event_type' => $eventData['event_type'],
                    'source_type' => $eventData['source_type'] ?? null,
                    'source_id' => $eventData['source_id'] ?? null,
                    'subject_type' => $eventData['subject_type'] ?? null,
                    'subject_id' => $eventData['subject_id'] ?? null,
                    'actor_user_id' => $eventData['actor_user_id'] ?? null,
                    'organization_id' => $eventData['organization_id'] ?? null,
                    'clinic_id' => $eventData['clinic_id'] ?? null,
                    'level' => $eventData['level'] ?? 'info',
                    'title' => $eventData['title'],
                    'message' => $eventData['message'],
                    'target_url' => $eventData['target_url'] ?? null,
                    'payload' => $eventData['payload'] ?? [],
                    'occurred_at' => $eventData['occurred_at'] ?? now(),
                ],
            );

            foreach ($recipients as $recipientSpec) {
                $user = $recipientSpec['user'] ?? null;

                if (! $user instanceof User || ! $user->status) {
                    continue;
                }

                $panel = $recipientSpec['panel'] ?? null;
                $channels = array_values(array_unique($recipientSpec['channels'] ?? ['in_app']));

                foreach ($channels as $channel) {
                    $destination = $channel === 'email' ? $user->email : (string) $user->getKey();
                    $deliveryKey = hash('sha256', implode('|', [
                        $event->getKey(),
                        $user->getKey(),
                        $panel,
                        $channel,
                        $destination,
                    ]));

                    $delivery = NotificationDelivery::query()->firstOrCreate(
                        ['delivery_key' => $deliveryKey],
                        [
                            'notification_event_id' => $event->getKey(),
                            'recipient_user_id' => $user->getKey(),
                            'panel' => $panel,
                            'channel' => $channel,
                            'status' => NotificationDelivery::STATUS_PENDING,
                            'destination' => $destination,
                            'meta' => [
                                'target_url' => $recipientSpec['target_url'] ?? $event->target_url,
                            ],
                        ],
                    );

                    if (! $delivery->wasRecentlyCreated) {
                        continue;
                    }

                    if ($channel === 'in_app') {
                        $this->deliverInApp($event, $delivery, $user, $recipientSpec, $inAppWriter);

                        continue;
                    }

                    DispatchNotificationDelivery::dispatch($delivery->getKey())->afterCommit();
                }
            }

            return $event;
        });
    }

    protected function deliverInApp(
        NotificationEvent $event,
        NotificationDelivery $delivery,
        User $user,
        array $recipientSpec,
        ?Closure $writer,
    ): void {
        try {
            if ($writer !== null) {
                $writer($event, $delivery, $user, $recipientSpec);
            }

            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_DELIVERED,
                'attempts' => 1,
                'delivered_at' => now(),
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_FAILED,
                'attempts' => 1,
                'failed_at' => now(),
                'last_error' => Str::limit($exception->getMessage(), 2000),
            ])->save();

            throw $exception;
        }
    }
}
