<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingWorkItemNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'billing_work_item_id',
        'user_id',
        'visibility',
        'body',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $note): void {
            if (blank($note->user_id) && auth()->check()) {
                $note->user_id = auth()->id();
            }
        });

        static::created(function (self $note): void {
            $note->workItem?->recordActivity('note_added', 'A work note was added.');
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
