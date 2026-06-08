<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicPortalCredentialOverride extends Model
{
    protected $fillable = [
        'organization_id',
        'clinic_id',
        'portal_credential_id',
        'portal_name',
        'portal_category',
        'login_url',
        'username',
        'password',
        'account_reference',
        'support_contact',
        'registration_qa_notes',
        'general_notes',
        'notes',
        'mfa_required',
        'mfa_method',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'username' => 'encrypted',
            'password' => 'encrypted',
            'mfa_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function portalCredential(): BelongsTo
    {
        return $this->belongsTo(PortalCredential::class);
    }
}
