<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerioChartEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'perio_chart_id',
        'tooth_number',
        'probing_depth_mb',
        'probing_depth_b',
        'probing_depth_db',
        'probing_depth_ml',
        'probing_depth_l',
        'probing_depth_dl',
        'recession_mb',
        'recession_b',
        'recession_db',
        'recession_ml',
        'recession_l',
        'recession_dl',
        'bleeding_mb',
        'bleeding_b',
        'bleeding_db',
        'bleeding_ml',
        'bleeding_l',
        'bleeding_dl',
        'mobility',
        'furcation',
        'suppuration',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bleeding_mb' => 'boolean',
            'bleeding_b' => 'boolean',
            'bleeding_db' => 'boolean',
            'bleeding_ml' => 'boolean',
            'bleeding_l' => 'boolean',
            'bleeding_dl' => 'boolean',
            'suppuration' => 'boolean',
        ];
    }

    public function perioChart(): BelongsTo
    {
        return $this->belongsTo(PerioChart::class);
    }
}
