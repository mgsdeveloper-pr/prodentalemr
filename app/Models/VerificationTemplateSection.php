<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class VerificationTemplateSection extends Model
{
    protected $fillable = [
        'organization_id',
        'clinic_id',
        'template_version_id',
        'source_section_id',
        'template_key',
        'section_key',
        'parent_section_key',
        'label',
        'sort_order',
        'is_builtin',
        'is_locked_by_admin',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'template_version_id' => 'integer',
            'source_section_id' => 'integer',
            'is_builtin' => 'boolean',
            'is_locked_by_admin' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeVisibleForClinic(Builder $query, ?int $clinicId = null, ?int $organizationId = null): Builder
    {
        return $query
            ->where(function (Builder $query) use ($clinicId): void {
                $query->whereNull('clinic_id');

                if (filled($clinicId)) {
                    $query->orWhere('clinic_id', $clinicId);
                }
            })
            ->when(filled($organizationId), function (Builder $query) use ($organizationId): void {
                $query->where(function (Builder $query) use ($organizationId): void {
                    $query
                        ->whereNull('organization_id')
                        ->orWhere('organization_id', $organizationId);
                });
            });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(VerificationTemplateVersion::class, 'template_version_id');
    }

    public function sourceSection(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_section_id');
    }

    public static function makeSectionKey(string $label, ?string $parentSectionKey = null): string
    {
        $base = Str::slug($label, '_') ?: 'custom_section';

        if (filled($parentSectionKey)) {
            return Str::limit($parentSectionKey . '_' . $base, 190, '');
        }

        return Str::limit('custom_' . $base, 190, '');
    }
}
