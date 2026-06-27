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

    public function scopeInClass(Builder $query, ?string $class): Builder
    {
        $normalized = static::normalizeClassValue($class);

        if (blank($normalized)) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($normalized): void {
            $builder
                ->where('class', $normalized)
                ->orWhere('class', 'like', $normalized . ' / %')
                ->orWhere('class', 'like', '% / ' . $normalized . ' / %')
                ->orWhere('class', 'like', '% / ' . $normalized);
        });
    }

    public function getClassTokensAttribute(): array
    {
        return static::classTokensFromValue($this->class);
    }

    public static function normalizeClassValue(mixed $value): ?string
    {
        $tokens = static::classTokensFromValue($value);

        return $tokens === [] ? null : implode(' / ', $tokens);
    }

    public static function classTokensFromValue(mixed $value): array
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return [];
        }

        $segments = preg_split('/\s*\/\s*/', $raw) ?: [];
        $tokens = [];

        foreach ($segments as $segment) {
            $token = trim(preg_replace('/\s+/', ' ', (string) $segment) ?? '');

            if ($token === '') {
                continue;
            }

            $key = mb_strtolower($token);

            if (! array_key_exists($key, $tokens)) {
                $tokens[$key] = $token;
            }
        }

        return array_values($tokens);
    }
}
