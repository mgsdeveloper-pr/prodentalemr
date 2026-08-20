<?php

namespace App\Models;

use App\Traits\HasPublicId;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdaProcedureCode extends Model
{
    use HasPublicId;

    public const LIFECYCLE_ACTIVE = 'active';
    public const LIFECYCLE_INACTIVE = 'inactive';
    public const LIFECYCLE_DEPRECATED = 'deprecated';
    public const LIFECYCLE_REMOVED_BY_ADA = 'removed_by_ada';

    public const LIFECYCLE_OPTIONS = [
        self::LIFECYCLE_ACTIVE => 'Active',
        self::LIFECYCLE_INACTIVE => 'Inactive',
        self::LIFECYCLE_DEPRECATED => 'Deprecated',
        self::LIFECYCLE_REMOVED_BY_ADA => 'Removed by ADA',
    ];

    protected $fillable = [
        'procedure_code',
        'description',
        'class',
        'is_active',
        'lifecycle_status',
        'source_year',
        'source_document',
        'source_page',
        'effective_date',
        'retired_at',
        'retirement_reason',
        'governance_notes',
        'last_reviewed_at',
        'last_reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'source_year' => 'integer',
            'source_page' => 'integer',
            'effective_date' => 'date',
            'retired_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('lifecycle_status')
                    ->orWhere('lifecycle_status', self::LIFECYCLE_ACTIVE);
            });
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
