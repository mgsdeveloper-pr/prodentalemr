<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceCarrier extends Model
{
    use SoftDeletes;

    protected $fillable = [
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

    public function overrides(): HasMany
    {
        return $this->hasMany(ClinicInsuranceCarrierOverride::class);
    }

    public function networkProfile(): HasOne
    {
        return $this->hasOne(InsuranceCarrierNetworkProfile::class);
    }

    public function overrideForClinic(?int $clinicId): ?ClinicInsuranceCarrierOverride
    {
        if (! $clinicId) {
            return null;
        }

        if ($this->relationLoaded('overrides')) {
            return $this->overrides->firstWhere('clinic_id', $clinicId);
        }

        return $this->overrides()
            ->where('clinic_id', $clinicId)
            ->first();
    }

    public function effectiveAttributesForClinic(?int $clinicId): array
    {
        $override = $this->overrideForClinic($clinicId);

        return [
            'insurance_name' => $override?->insurance_name ?: $this->insurance_name,
            'payer_id' => $override?->payer_id ?: $this->payer_id,
            'payer_phone' => $override?->payer_phone ?: $this->payer_phone,
            'claims_address' => $override?->claims_address ?: $this->claims_address,
            'website' => $override?->website ?: $this->website,
            'notes' => $override?->notes ?: $this->notes,
            'is_active' => $override?->is_active ?? $this->is_active,
            'has_override' => (bool) $override,
        ];
    }
}
