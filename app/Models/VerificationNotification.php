<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationNotification extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'clinic_id',
        'billing_work_item_id',
        'actor_user_id',
        'panel',
        'activity_type',
        'level',
        'title',
        'message',
        'target_url',
        'meta',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(BillingWorkItem::class, 'billing_work_item_id');
    }
}
