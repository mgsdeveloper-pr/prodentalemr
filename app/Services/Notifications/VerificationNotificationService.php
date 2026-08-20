<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\NotificationDelivery;
use App\Models\VerificationNotification;
use Illuminate\Database\Eloquent\Builder;

class VerificationNotificationService
{
    public function markRead(VerificationNotification $notification): void
    {
        if ($notification->read_at !== null) {
            return;
        }

        $notification->forceFill(['read_at' => now()])->save();

        if ($notification->notification_delivery_id) {
            NotificationDelivery::query()
                ->whereKey($notification->notification_delivery_id)
                ->whereNull('read_at')
                ->update(['read_at' => $notification->read_at]);
        }
    }

    public function markAllReadForPanel(User $user, string $panel, ?int $clinicId = null): void
    {
        $this->markQueryRead(
            VerificationNotification::query()
                ->where('user_id', $user->getAuthIdentifier())
                ->where('panel', $panel)
                ->when(filled($clinicId), fn (Builder $query) => $query->where('clinic_id', $clinicId)),
        );
    }

    public function markQueryRead(Builder $query): void
    {
        $deliveryIds = (clone $query)
            ->whereNull('read_at')
            ->whereNotNull('notification_delivery_id')
            ->pluck('notification_delivery_id');

        $readAt = now();

        (clone $query)
            ->whereNull('read_at')
            ->update(['read_at' => $readAt]);

        NotificationDelivery::query()
            ->whereIn('id', $deliveryIds)
            ->whereNull('read_at')
            ->update(['read_at' => $readAt]);
    }
}
