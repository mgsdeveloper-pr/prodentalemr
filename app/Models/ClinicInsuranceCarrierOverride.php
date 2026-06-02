<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicInsuranceCarrierOverride extends Model
{
    protected $fillable = [
        'organization_id',
        'clinic_id',
        'insurance_carrier_id',
        'insurance_name',
        'payer_id',
        'payer_phone',
        'claims_address',
        'website',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
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

    public function insuranceCarrier(): BelongsTo
    {
        return $this->belongsTo(InsuranceCarrier::class);
    }
}
