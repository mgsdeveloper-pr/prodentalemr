<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationFormSubmission extends Model
{
    protected $fillable = [
        'billing_work_item_id',
        'user_id',
        'panel',
        'status',
        'outcome_status',
        'priority',
        'version',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'payload' => 'array',
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
}
