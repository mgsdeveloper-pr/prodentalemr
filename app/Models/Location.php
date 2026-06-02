<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'location_name',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'phone',
        'status',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function operatories(): HasMany
    {
        return $this->hasMany(ClinicOperatory::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function consentForms(): HasMany
    {
        return $this->hasMany(PatientConsentForm::class);
    }

    public function serviceEnrollments(): HasMany
    {
        return $this->hasMany(ClientServiceEnrollment::class);
    }

    public function billingWorkItems(): HasMany
    {
        return $this->hasMany(BillingWorkItem::class);
    }
}
