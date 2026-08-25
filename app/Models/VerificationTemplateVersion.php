<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VerificationTemplateVersion extends Model
{
    use HasPublicId, SoftDeletes;

    public const SCOPE_MASTER = 'master';

    public const SCOPE_CLINIC = 'clinic';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const FORM_TYPE_BOTH = 'both';

    public const FORM_TYPE_FULL = 'full_form';

    public const FORM_TYPE_SHORT = 'short_form';

    public const FORM_TYPE_OPTIONS = [
        self::FORM_TYPE_BOTH => 'Full + Short',
        self::FORM_TYPE_FULL => 'Full Form',
        self::FORM_TYPE_SHORT => 'Short Form',
    ];

    public const CLINIC_VISIBILITY_HIDDEN = 'hidden';

    public const CLINIC_VISIBILITY_VISIBLE = 'visible_to_clinics';

    public const CLINIC_VISIBILITY_DEFAULT = 'default_for_new_clinics';

    public const CLINIC_VISIBILITY_RETIRED = 'retired';

    public const CLINIC_VISIBILITY_OPTIONS = [
        self::CLINIC_VISIBILITY_HIDDEN => 'Internal Only',
        self::CLINIC_VISIBILITY_VISIBLE => 'Available to Clinics',
        self::CLINIC_VISIBILITY_DEFAULT => 'Default for New Clinics',
        self::CLINIC_VISIBILITY_RETIRED => 'Retired',
    ];

    protected $fillable = [
        'template_key',
        'scope',
        'organization_id',
        'clinic_id',
        'parent_version_id',
        'source_version_id',
        'version_number',
        'name',
        'form_type',
        'clinic_visibility',
        'status',
        'is_active',
        'is_working_draft',
        'published_at',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_working_draft' => 'boolean',
            'version_number' => 'integer',
            'published_at' => 'datetime',
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

    public function parentVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_version_id');
    }

    public function sourceVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_version_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(VerificationFormQuestion::class, 'template_version_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(VerificationTemplateSection::class, 'template_version_id');
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(BillingWorkItem::class, 'verification_template_version_id');
    }

    public function derivedVersions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_version_id');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isAvailableToClinics(): bool
    {
        return in_array($this->clinic_visibility, [
            self::CLINIC_VISIBILITY_VISIBLE,
            self::CLINIC_VISIBILITY_DEFAULT,
        ], true);
    }

    public function clinicAvailabilityLabel(): string
    {
        if ($this->status === self::STATUS_DRAFT) {
            return 'Decided when publishing';
        }

        return $this->isAvailableToClinics() ? 'Available to Clinics' : match ($this->clinic_visibility) {
            self::CLINIC_VISIBILITY_RETIRED => 'Retired',
            default => 'Internal Only',
        };
    }

    public function requestUsageCount(): int
    {
        return BillingWorkItem::withTrashed()
            ->where('verification_template_version_id', $this->getKey())
            ->count();
    }

    public function dependentVersionCount(): int
    {
        return self::withTrashed()
            ->where($this->getKeyName(), '!=', $this->getKey())
            ->where(function ($query): void {
                $query->where('parent_version_id', $this->getKey())
                    ->orWhere('source_version_id', $this->getKey());
            })
            ->count();
    }

    public function isUnusedDraft(): bool
    {
        return $this->exists
            && $this->status === self::STATUS_DRAFT
            && blank($this->published_at)
            && $this->requestUsageCount() === 0
            && $this->dependentVersionCount() === 0;
    }

    public function canEditDirectly(): bool
    {
        return $this->isUnusedDraft();
    }

    public function canDeletePermanently(): bool
    {
        return $this->isUnusedDraft();
    }

    public function lifecycleLockReason(): ?string
    {
        if ($this->status !== self::STATUS_DRAFT || filled($this->published_at)) {
            return 'Published and historical templates are read-only. Create a new draft to make changes.';
        }

        if ($this->requestUsageCount() > 0) {
            return 'This template is used by a verification request and is retained for record history.';
        }

        if ($this->dependentVersionCount() > 0) {
            return 'Another template was created from this draft, so it is retained as a source record.';
        }

        return null;
    }
}
