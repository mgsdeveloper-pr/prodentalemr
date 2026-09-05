<?php

namespace App\Models;

use App\Support\InsurancePhoneNumber;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicInsuranceCarrierOverride extends Model
{
    use HasPublicId;

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

    protected function payerPhone(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): ?string => InsurancePhoneNumber::normalize($value),
        );
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
