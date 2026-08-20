<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use App\Services\Notifications\NotificationDeliveryProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class DispatchNotificationDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public int $deliveryId)
    {
        $this->onQueue('notifications');
    }

    public function handle(NotificationDeliveryProcessor $processor): void
    {
        $delivery = NotificationDelivery::query()->find($this->deliveryId);

        if ($delivery) {
            $processor->process($delivery);
        }
    }

    public function failed(?Throwable $exception): void
    {
        NotificationDelivery::query()
            ->whereKey($this->deliveryId)
            ->update([
                'status' => NotificationDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'last_error' => $exception?->getMessage(),
            ]);
    }
}
