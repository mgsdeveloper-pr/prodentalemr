<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteRegistration extends Model
{
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

    public function checkout()
    {
        return $this->hasOne(WebsiteCheckout::class);
    }
}
