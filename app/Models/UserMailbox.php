<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMailbox extends Model
{
    protected $fillable = [
        'user_id',
        'enabled',
        'provider_label',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'imap_validate_certificate',
        'imap_username',
        'imap_password',
        'inbox_folder',
        'spam_folder',
        'sent_folder',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'from_name',
        'from_address',
        'attachment_limit_mb',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'enabled' => 'boolean',
            'imap_port' => 'integer',
            'imap_validate_certificate' => 'boolean',
            'imap_password' => 'encrypted',
            'smtp_port' => 'integer',
            'smtp_password' => 'encrypted',
            'attachment_limit_mb' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function defaultState(int $userId, ?string $defaultName = null, ?string $defaultEmail = null): array
    {
        return [
            'user_id' => $userId,
            'enabled' => false,
            'provider_label' => 'Meditya Mail',
            'imap_host' => 'mail.medityaglobalservices.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'imap_validate_certificate' => false,
            'imap_username' => $defaultEmail,
            'imap_password' => null,
            'inbox_folder' => 'INBOX',
            'spam_folder' => 'INBOX.Spam',
            'sent_folder' => 'INBOX.Sent',
            'smtp_host' => 'mail.medityaglobalservices.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
            'smtp_username' => $defaultEmail,
            'smtp_password' => null,
            'from_name' => $defaultName,
            'from_address' => $defaultEmail,
            'attachment_limit_mb' => 25,
        ];
    }
}
