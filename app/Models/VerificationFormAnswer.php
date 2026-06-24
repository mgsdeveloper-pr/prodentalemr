<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationFormAnswer extends Model
{
    protected $fillable = [
        'billing_work_item_id',
        'verification_form_question_id',
        'answer_value',
        'note_value',
    ];

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(BillingWorkItem::class, 'billing_work_item_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(VerificationFormQuestion::class, 'verification_form_question_id');
    }
}
