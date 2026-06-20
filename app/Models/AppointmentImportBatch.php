<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentImportBatch extends Model
{
    protected $fillable = [
        'organization_id',
        'clinic_id',
        'user_id',
        'original_filename',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'warning_rows',
        'row_results',
        'failed_row_results',
    ];

    protected function casts(): array
    {
        return [
            'row_results' => 'array',
            'failed_row_results' => 'array',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
