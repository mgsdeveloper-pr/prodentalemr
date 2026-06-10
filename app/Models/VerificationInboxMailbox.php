<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationInboxMailbox extends Model
{
    protected $fillable = [
        'clinic_id',
        'verification_inbox_enabled',
        'verification_inbox_provider',
        'verification_inbox_host',
        'verification_inbox_port',
        'verification_inbox_protocol',
        'verification_inbox_encryption',
        'verification_inbox_validate_certificate',
        'verification_inbox_username',
        'verification_inbox_password',
        'verification_inbox_folder_inbox',
        'verification_inbox_folder_spam',
        'verification_inbox_sync_frequency_minutes',
        'verification_inbox_sync_window_days',
        'verification_inbox_retention_mode',
        'verification_inbox_retention_days',
        'verification_inbox_keep_latest_count',
        'verification_inbox_spam_retention_days',
        'verification_inbox_preserve_flagged',
        'verification_inbox_auto_cleanup_enabled',
        'verification_inbox_last_synced_at',
        'verification_inbox_last_cleanup_at',
    ];

    protected function casts(): array
    {
        return [
            'clinic_id' => 'integer',
            'verification_inbox_enabled' => 'boolean',
            'verification_inbox_port' => 'integer',
            'verification_inbox_validate_certificate' => 'boolean',
            'verification_inbox_password' => 'encrypted',
            'verification_inbox_sync_frequency_minutes' => 'integer',
            'verification_inbox_sync_window_days' => 'integer',
            'verification_inbox_retention_days' => 'integer',
            'verification_inbox_keep_latest_count' => 'integer',
            'verification_inbox_spam_retention_days' => 'integer',
            'verification_inbox_preserve_flagged' => 'boolean',
            'verification_inbox_auto_cleanup_enabled' => 'boolean',
            'verification_inbox_last_synced_at' => 'datetime',
            'verification_inbox_last_cleanup_at' => 'datetime',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public static function defaultState(int $clinicId): array
    {
        return [
            'clinic_id' => $clinicId,
            'verification_inbox_enabled' => false,
            'verification_inbox_provider' => null,
            'verification_inbox_host' => null,
            'verification_inbox_port' => 993,
            'verification_inbox_protocol' => 'imap',
            'verification_inbox_encryption' => 'ssl',
            'verification_inbox_validate_certificate' => false,
            'verification_inbox_username' => null,
            'verification_inbox_password' => null,
            'verification_inbox_folder_inbox' => 'INBOX',
            'verification_inbox_folder_spam' => 'INBOX.Spam',
            'verification_inbox_sync_frequency_minutes' => 15,
            'verification_inbox_sync_window_days' => 90,
            'verification_inbox_retention_mode' => 'days',
            'verification_inbox_retention_days' => 90,
            'verification_inbox_keep_latest_count' => 5000,
            'verification_inbox_spam_retention_days' => 30,
            'verification_inbox_preserve_flagged' => true,
            'verification_inbox_auto_cleanup_enabled' => true,
        ];
    }
}
