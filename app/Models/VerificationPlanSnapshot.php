<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationPlanSnapshot extends Model
{
    public const PRIORITY_OPTIONS = [
        'primary' => 'Primary',
        'secondary' => 'Secondary',
        'tertiary' => 'Tertiary',
    ];

    protected $fillable = [
        'billing_work_item_id',
        'plan_priority',
        'payer_name',
        'member_id',
        'group_number',
        'subscriber_name',
        'subscriber_dob',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subscriber_dob' => 'date',
        ];
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(BillingWorkItem::class, 'billing_work_item_id');
    }
}
