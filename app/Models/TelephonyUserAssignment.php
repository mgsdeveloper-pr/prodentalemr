<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelephonyUserAssignment extends Model
{
    protected $fillable = [
        'telephony_account_id',
        'user_id',
        'provider_user_id',
        'extension',
        'user_key',
        'can_call',
        'can_access_recordings',
        'can_use_ai_summary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'user_key' => 'encrypted',
            'can_call' => 'boolean',
            'can_access_recordings' => 'boolean',
            'can_use_ai_summary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected $hidden = ['user_key'];

    public function telephonyAccount(): BelongsTo
    {
        return $this->belongsTo(TelephonyAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
