<?php

namespace App\Support;

use App\Models\BillingWorkItemAttachment;
use App\Models\SaasEntitlementAuditLog;

class BillingWorkItemAttachmentSupportAudit
{
    public static function canAccess(BillingWorkItemAttachment $attachment): bool
    {
        $attachment->loadMissing('workItem');
        $workItem = $attachment->workItem;

        if (! $workItem) {
            return false;
        }

        return SaasSupportAccess::matchesScope((int) $workItem->organization_id, (int) $workItem->clinic_id);
    }

    public static function recordAccess(string $eventType, BillingWorkItemAttachment $attachment): void
    {
        $context = SaasSupportAccess::active();

        if (! $context) {
            return;
        }

        $attachment->loadMissing('workItem');
        $workItem = $attachment->workItem;

        if (! $workItem) {
            return;
        }

        SaasEntitlementAuditLog::query()->create([
            'organization_id' => $workItem->organization_id,
            'clinic_id' => $workItem->clinic_id,
            'actor_user_id' => data_get($context, 'actor_user_id', auth()->id()),
            'event_type' => $eventType,
            'entity_type' => $attachment::class,
            'entity_id' => $attachment->getKey(),
            'after_values' => [
                'support_session_id' => data_get($context, 'session_id'),
                'support_reason' => data_get($context, 'reason'),
                'support_started_at' => data_get($context, 'started_at'),
                'document' => self::metadata($attachment),
            ],
            'notes' => data_get($context, 'reason'),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    protected static function metadata(BillingWorkItemAttachment $attachment): array
    {
        $workItem = $attachment->workItem;

        return [
            'attachment_id' => $attachment->getKey(),
            'billing_work_item_id' => $attachment->billing_work_item_id,
            'reference_number' => $workItem?->reference_number,
            'title' => $attachment->title,
            'original_file_name' => $attachment->original_file_name,
            'mime_type' => $attachment->mime_type,
            'file_size' => $attachment->file_size,
        ];
    }
}
