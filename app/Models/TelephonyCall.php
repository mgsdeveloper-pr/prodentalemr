<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelephonyCall extends Model
{
    use HasPublicId;

    public const TERMINAL_STATUSES = ['completed', 'failed'];

    private const STATUS_TRANSITIONS = [
        'initiated' => ['ringing', 'connected', 'completed', 'failed'],
        'ringing' => ['connected', 'completed', 'failed'],
        'connected' => ['completed', 'failed'],
    ];

    protected $fillable = [
        'telephony_account_id',
        'organization_id',
        'clinic_id',
        'billing_work_item_id',
        'user_id',
        'provider',
        'provider_call_id',
        'direction',
        'from_number',
        'to_number',
        'status',
        'started_at',
        'answered_at',
        'ended_at',
        'duration_seconds',
        'recording_url',
        'recording_duration_seconds',
        'transcript',
        'ai_summary',
        'ai_review_status',
        'estimated_cost',
        'provider_payload',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'answered_at' => 'datetime',
            'ended_at' => 'datetime',
            'recording_url' => 'encrypted',
            'transcript' => 'encrypted',
            'ai_summary' => 'encrypted:array',
            'provider_payload' => 'encrypted:array',
            'estimated_cost' => 'decimal:4',
        ];
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function canTransitionTo(string $status): bool
    {
        if ($status === $this->status) {
            return true;
        }

        if ($this->isTerminal()) {
            return false;
        }

        return in_array($status, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    public function telephonyAccount(): BelongsTo
    {
        return $this->belongsTo(TelephonyAccount::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
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
