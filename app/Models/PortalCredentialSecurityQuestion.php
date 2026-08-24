<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalCredentialSecurityQuestion extends Model
{
    protected $fillable = [
        'portal_credential_id',
        'question',
        'answer',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'question' => 'encrypted',
            'answer' => 'encrypted',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function portalCredential(): BelongsTo
    {
        return $this->belongsTo(PortalCredential::class);
    }
}
