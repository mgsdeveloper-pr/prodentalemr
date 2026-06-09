<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VerificationInboxAttachment extends Model
{
    protected $fillable = [
        'verification_inbox_message_id',
        'file_name',
        'mime_type',
        'file_size',
        'part_number',
        'content_id',
        'is_inline',
        'storage_disk',
        'storage_path',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_inline' => 'boolean',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(VerificationInboxMessage::class, 'verification_inbox_message_id');
    }

    public function isAvailable(): bool
    {
        return filled($this->storage_path)
            && Storage::disk($this->storage_disk ?: 'verification_inbox')->exists($this->storage_path);
    }
}
