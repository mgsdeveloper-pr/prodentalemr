<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelephonyUserAssignment extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $assignment): void {
            $assignment->provider_user_id = filled($assignment->provider_user_id)
                ? trim($assignment->provider_user_id) : null;
            $others = static::query()->when($assignment->exists,
                fn ($query) => $query->whereKeyNot($assignment->id));
            if ((clone $others)->where('user_id', $assignment->user_id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'user_id' => 'This user already has a calling identity. Edit their existing assignment.',
                ]);
            }
            if ($assignment->provider_user_id !== null
                && (clone $others)->whereRaw('LOWER(provider_user_id) = ?', [mb_strtolower($assignment->provider_user_id)])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'provider_user_id' => 'This MightyCall agent ID is already assigned to another user.',
                ]);
            }
        });
    }

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
