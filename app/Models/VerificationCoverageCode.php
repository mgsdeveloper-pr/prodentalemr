<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationCoverageCode extends Model
{
    protected $fillable = [
        'billing_work_item_id',
        'code_system',
        'category',
        'code',
        'description',
        'coverage_status',
        'coverage_percent',
        'frequency',
        'age_limit',
        'waiting_period',
        'service_history',
        'pre_auth_required',
        'pre_auth_details',
        'downgrade_applies',
        'downgrade_to',
        'payment_guideline',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'coverage_percent' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(BillingWorkItem::class, 'billing_work_item_id');
    }
}
