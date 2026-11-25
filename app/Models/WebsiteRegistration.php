<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteRegistration extends Model
{
    public const STATUS_LABELS = [
        OnboardingDraft::STATUS_DRAFT => 'Incomplete',
        OnboardingDraft::STATUS_SUBMITTED => 'Submitted',
        OnboardingDraft::STATUS_UNDER_REVIEW => 'Under review',
        OnboardingDraft::STATUS_CHANGES_REQUESTED => 'Changes requested',
        OnboardingDraft::STATUS_APPROVED => 'Approved',
        OnboardingDraft::STATUS_ACTIVATED => 'Activated',
        OnboardingDraft::STATUS_EXPIRED => 'Expired',
    ];

    protected $guarded = ['id'];
    protected $hidden = ['resume_hash', 'access_hash'];

    protected function casts(): array
    {
        return ['resume_expires_at' => 'datetime', 'access_expires_at' => 'datetime', 'email_verified_at' => 'datetime'];
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(OnboardingDraft::class, 'onboarding_draft_id');
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? str($status)->headline()->toString();
    }

    public function checkout()
    {
        return $this->hasOne(WebsiteCheckout::class);
    }
}
