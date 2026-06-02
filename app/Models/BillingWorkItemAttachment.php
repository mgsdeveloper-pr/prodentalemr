<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class BillingWorkItemAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'billing_work_item_id',
        'user_id',
        'title',
        'file_path',
        'original_file_name',
        'mime_type',
        'file_size',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $attachment): void {
            if (blank($attachment->user_id) && auth()->check()) {
                $attachment->user_id = auth()->id();
            }
        });

        static::saving(function (self $attachment): void {
            if (filled($attachment->file_path) && Storage::disk('local')->exists($attachment->file_path)) {
                $attachment->mime_type = Storage::disk('local')->mimeType($attachment->file_path) ?: $attachment->mime_type;
                $attachment->file_size = Storage::disk('local')->size($attachment->file_path) ?: $attachment->file_size;
                $attachment->original_file_name = $attachment->original_file_name ?: basename($attachment->file_path);
            }
        });

        static::created(function (self $attachment): void {
            $attachment->workItem?->recordActivity(
                'attachment_added',
                'Attachment uploaded: ' . ($attachment->title ?: $attachment->original_file_name ?: 'Supporting document') . '.',
                [
                    'attachment_id' => $attachment->getKey(),
                    'title' => $attachment->title,
                    'original_file_name' => $attachment->original_file_name,
                    'mime_type' => $attachment->mime_type,
                    'notes' => $attachment->notes,
                ],
            );
        });
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
