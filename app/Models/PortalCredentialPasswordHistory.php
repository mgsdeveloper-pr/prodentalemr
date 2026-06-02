<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalCredentialPasswordHistory extends Model
{
    protected $fillable = [
        'portal_credential_id',
        'organization_id',
        'clinic_id',
        'changed_by_user_id',
        'changed_by_name',
        'password_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'password_snapshot' => 'encrypted',
        ];
    }

    public function portalCredential(): BelongsTo
    {
        return $this->belongsTo(PortalCredential::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
