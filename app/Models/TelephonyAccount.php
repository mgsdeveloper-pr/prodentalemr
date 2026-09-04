<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TelephonyAccount extends Model
{
    use HasPublicId, SoftDeletes;

    public const PROVIDER_MIGHTYCALL = 'mightycall';

    protected $fillable = [
        'organization_id',
        'name',
        'provider',
        'api_key',
        'api_secret',
        'business_number',
        'webphone_sdk_url',
        'recording_enabled',
        'transcription_enabled',
        'ai_summary_enabled',
        'recording_retention_days',
        'monthly_minute_limit',
        'recording_announcement',
        'webhook_token',
        'is_platform_default',
        'is_active',
        'last_connected_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'api_secret' => 'encrypted',
            'recording_enabled' => 'boolean',
            'transcription_enabled' => 'boolean',
            'ai_summary_enabled' => 'boolean',
            'is_platform_default' => 'boolean',
            'is_active' => 'boolean',
            'last_connected_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TelephonyAccount $account): void {
            $account->webhook_token ??= Str::random(48);
            $account->webphone_sdk_url ??= 'https://ccapi.mightycall.com/v4/sdk/mightycall.webphone.sdk.js';
        });

        static::saving(function (TelephonyAccount $account): void {
            if ($account->is_platform_default) {
                static::query()
                    ->whereKeyNot($account->getKey())
                    ->where('provider', $account->provider)
                    ->update(['is_platform_default' => false]);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function userAssignments(): HasMany
    {
        return $this->hasMany(TelephonyUserAssignment::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(TelephonyCall::class);
    }

    public function getScopeLabelAttribute(): string
    {
        return $this->organization?->name ?? 'Platform default';
    }

    public function webhookUrl(): string
    {
        return route('webhooks.telephony.mightycall', [
            'account' => $this->public_id,
            'token' => $this->webhook_token,
        ]);
    }
}
