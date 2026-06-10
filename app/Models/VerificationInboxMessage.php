<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VerificationInboxMessage extends Model
{
    protected $fillable = [
        'clinic_id',
        'mailbox_uid',
        'folder_name',
        'folder_type',
        'external_message_id',
        'message_hash',
        'subject',
        'from_name',
        'from_email',
        'reply_to_email',
        'to_emails',
        'cc_emails',
        'bcc_emails',
        'snippet',
        'body_text',
        'body_html',
        'headers',
        'received_at',
        'synced_at',
        'is_read',
        'is_flagged',
        'is_spam',
        'has_attachments',
        'attachment_count',
        'size_bytes',
        'is_protected',
    ];

    protected function casts(): array
    {
        return [
            'clinic_id' => 'integer',
            'to_emails' => 'array',
            'cc_emails' => 'array',
            'bcc_emails' => 'array',
            'headers' => 'array',
            'received_at' => 'datetime',
            'synced_at' => 'datetime',
            'is_read' => 'boolean',
            'is_flagged' => 'boolean',
            'is_spam' => 'boolean',
            'has_attachments' => 'boolean',
            'attachment_count' => 'integer',
            'size_bytes' => 'integer',
            'is_protected' => 'boolean',
        ];
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VerificationInboxAttachment::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function senderLabel(): string
    {
        if (filled($this->from_name) && filled($this->from_email)) {
            return $this->from_name . ' <' . $this->from_email . '>';
        }

        return $this->from_email ?: ($this->from_name ?: 'Unknown sender');
    }

    public function senderDisplayName(): string
    {
        return $this->from_name ?: ($this->from_email ?: 'Unknown sender');
    }

    public function receivedLabel(): string
    {
        return $this->received_at?->format('d M Y, h:i A') ?? 'Unknown time';
    }

    public function shortReceivedLabel(): string
    {
        $receivedAt = $this->received_at;

        if (! $receivedAt instanceof Carbon) {
            return 'Unknown';
        }

        if ($receivedAt->isToday()) {
            return $receivedAt->format('h:i A');
        }

        if ($receivedAt->isCurrentYear()) {
            return $receivedAt->format('d M');
        }

        return $receivedAt->format('d M Y');
    }

    public function previewSnippet(): string
    {
        $source = $this->snippet ?: $this->body_text ?: strip_tags((string) $this->body_html);

        return Str::limit(trim(preg_replace('/\s+/', ' ', (string) $source)), 180);
    }

    public function sanitizedHtmlBody(): string
    {
        $html = (string) ($this->body_html ?: '');

        if ($html === '') {
            return nl2br(e((string) $this->body_text));
        }

        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html) ?? $html;
        $html = preg_replace('/\son[a-z]+\s*=\s*"[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace("/\son[a-z]+\s*=\s*'[^']*'/i", '', $html) ?? $html;
        $html = preg_replace('/\son[a-z]+\s*=\s*[^\s>]+/i', '', $html) ?? $html;

        return $html;
    }

    public function securityCodes(): Collection
    {
        $haystack = implode("\n", array_filter([
            $this->subject,
            $this->snippet,
            $this->body_text,
            strip_tags((string) $this->body_html),
        ]));

        preg_match_all('/\b(?:otp|code|passcode|verification code|security code)?\s*[:#-]?\s*(\d{4,8})\b/i', $haystack, $matches);

        return collect($matches[1] ?? [])
            ->filter()
            ->unique()
            ->values();
    }
}
