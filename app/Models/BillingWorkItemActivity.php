<?php

namespace App\Models;

use App\Support\VerificationNotificationCenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingWorkItemActivity extends Model
{
    protected $fillable = [
        'billing_work_item_id',
        'user_id',
        'activity_type',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(BillingWorkItem::class, 'billing_work_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $activity): void {
            VerificationNotificationCenter::dispatchForActivity($activity);
        });
    }
}
