<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'default_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function patientLedgerEntries(): HasMany
    {
        return $this->hasMany(PatientLedgerEntry::class);
    }

    public function insuranceClaimLineItems(): HasMany
    {
        return $this->hasMany(PatientInsuranceClaimLineItem::class);
    }
}
