<?php

namespace App\Services\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;

class DeliveryService
{
    public function recordDelivery(BillingWorkItem $request, string $channel, ?User $actor = null): void
    {
        app(TimelineService::class)->record($request, 'verification_delivered', 'Verification report delivered.', [
            'channel' => $channel,
            'user_name' => $actor?->name,
        ], $actor);
    }

    public function recordResend(BillingWorkItem $request, string $channel, ?User $actor = null): void
    {
        app(TimelineService::class)->record($request, 'verification_delivery_resent', 'Verification report delivery resent.', [
            'channel' => $channel,
            'user_name' => $actor?->name,
        ], $actor);
    }

    public function recordPdfAccess(BillingWorkItem $request, string $action, string $panel, string $mode, ?User $actor = null): void
    {
        app(TimelineService::class)->record($request, "verification_pdf_{$action}", "Verification PDF {$action}.", [
            'panel' => $panel,
            'output_mode' => $mode,
            'user_name' => $actor?->name,
        ], $actor);
    }

    public function deliverySnapshot(BillingWorkItem $request): array
    {
        $latestDelivery = $request->activities()
            ->where('activity_type', 'verification_delivered')
            ->latest('created_at')
            ->latest('id')
            ->first();

        $latestResend = $request->activities()
            ->where('activity_type', 'verification_delivery_resent')
            ->latest('created_at')
            ->latest('id')
            ->first();

        $latestPdf = $request->activities()
            ->whereIn('activity_type', ['verification_pdf_downloaded', 'verification_pdf_previewed'])
            ->latest('created_at')
            ->latest('id')
            ->first();

        $latestDeliveryEvent = collect([$latestDelivery, $latestResend])
            ->filter()
            ->sortByDesc(fn ($activity): string => sprintf(
                '%s.%010d',
                optional($activity->created_at)->format('YmdHisu') ?: '00000000000000000000',
                (int) $activity->getKey()
            ))
            ->first();

        return [
            'is_delivered' => $latestDelivery !== null,
            'status_label' => $latestDelivery ? 'Delivered' : 'Not Delivered',
            'channel' => $this->formatChannel(data_get($latestDeliveryEvent?->meta, 'channel')),
            'delivered_at' => optional($latestDelivery?->created_at)->format('M d, Y h:i A') ?: '-',
            'delivered_by' => $latestDelivery?->user?->name ?: (data_get($latestDelivery?->meta, 'user_name') ?: '-'),
            'last_event' => $latestDeliveryEvent
                ? str($latestDeliveryEvent->activity_type)->replace('_', ' ')->headline()->toString()
                : '-',
            'last_event_at' => optional($latestDeliveryEvent?->created_at)->format('M d, Y h:i A') ?: '-',
            'last_pdf_action' => $latestPdf
                ? str(str_replace('verification_pdf_', '', $latestPdf->activity_type))->headline()->toString()
                : '-',
            'last_pdf_mode' => filled(data_get($latestPdf?->meta, 'output_mode'))
                ? str((string) data_get($latestPdf->meta, 'output_mode'))->replace('_', ' ')->headline()->toString()
                : '-',
            'last_pdf_at' => optional($latestPdf?->created_at)->format('M d, Y h:i A') ?: '-',
        ];
    }

    protected function formatChannel(?string $channel): string
    {
        if (blank($channel)) {
            return '-';
        }

        return str($channel)->replace('_', ' ')->headline()->toString();
    }
}
