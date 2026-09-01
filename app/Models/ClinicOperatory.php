<?php

namespace App\Models;

use App\Traits\HasPublicId;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicOperatory extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'name',
        'code',
        'display_order',
        'status',
        'notes',
        'business_hours',
        'schedule_exceptions',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'status' => 'boolean',
            'business_hours' => 'array',
            'schedule_exceptions' => 'array',
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

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'clinic_operatory_id');
    }
}
