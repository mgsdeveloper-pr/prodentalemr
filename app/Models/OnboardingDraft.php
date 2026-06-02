<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingDraft extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'last_completed_step',
        'data',
        'notification_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'notification_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
