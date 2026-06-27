<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VerificationTemplateSection extends Model
{
    protected $fillable = [
        'organization_id',
        'clinic_id',
        'template_key',
        'section_key',
        'parent_section_key',
        'label',
        'sort_order',
        'is_builtin',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_builtin' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
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
