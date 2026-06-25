<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdaProcedureCode extends Model
{
    protected $fillable = [
        'procedure_code',
        'description',
        'class',
        'is_active',
        'source_year',
        'source_document',
        'source_page',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'source_year' => 'integer',
            'source_page' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
