<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'dso_id',
        'name',
        'owner_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'status',
        'lifecycle_status',
        'onboarding_status',
        'account_manager_user_id',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class);
    }

    public function dso(): BelongsTo
    {
        return $this->belongsTo(Dso::class);
    }

    public function locations(): HasManyThrough
    {
        return $this->hasManyThrough(Location::class, Clinic::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_user_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function serviceEnrollments(): HasMany
    {
        return $this->hasMany(ClientServiceEnrollment::class);
    }

    public function billingWorkItems(): HasMany
    {
        return $this->hasMany(BillingWorkItem::class);
    }

    public function activeSubscription(): HasMany
    {
        return $this->hasMany(Subscription::class)->where('status', 'active');
    }

    public function currentSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->latest('start_date')
            ->first();
    }

    public function billingAddressLines(): array
    {
        $lines = [];

        if (filled($this->address)) {
            $lines[] = $this->address;
        }

        $cityStateZip = collect([
            $this->city,
            $this->state,
            $this->zip_code,
        ])->filter()->implode(', ');

        if (filled($cityStateZip)) {
            $lines[] = $cityStateZip;
        }

        if (filled($this->country)) {
            $lines[] = $this->country;
        }

        if (count($lines) > 0) {
            return $lines;
        }

        $fallbackLocation = $this->locations()->first();

        if (! $fallbackLocation) {
            return [];
        }

        $fallbackLines = [];

        if (filled($fallbackLocation->address)) {
            $fallbackLines[] = $fallbackLocation->address;
        }

        $fallbackCityStateZip = collect([
            $fallbackLocation->city,
            $fallbackLocation->state,
            $fallbackLocation->zip_code,
        ])->filter()->implode(', ');

        if (filled($fallbackCityStateZip)) {
            $fallbackLines[] = $fallbackCityStateZip;
        }

        if (filled($fallbackLocation->country)) {
            $fallbackLines[] = $fallbackLocation->country;
        }

        return $fallbackLines;
    }
}
