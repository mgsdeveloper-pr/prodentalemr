<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class PortalCredential extends Model
{
    use SoftDeletes;

    protected ?string $passwordBeforeUpdate = null;

    public const CATEGORY_OPTIONS = [
        'insurance' => 'Insurance',
        'clearinghouse' => 'Clearinghouse',
        'eligibility' => 'Eligibility',
        'payment' => 'Payment',
        'government' => 'Government',
        'other' => 'Other',
    ];

    public const MFA_METHOD_OPTIONS = [
        'none' => 'None',
        'email' => 'Email',
        'sms' => 'SMS',
        'authenticator_app' => 'Authenticator App',
        'security_question' => 'Security Question',
        'other' => 'Other',
    ];

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'portal_name',
        'portal_category',
        'login_url',
        'username',
        'password',
        'account_reference',
        'support_contact',
        'registration_qa_notes',
        'general_notes',
        'notes',
        'mfa_required',
        'mfa_method',
        'is_active',
        'visible_to_clinic',
    ];

    protected function casts(): array
    {
        return [
            'username' => 'encrypted',
            'password' => 'encrypted',
            'mfa_required' => 'boolean',
            'is_active' => 'boolean',
            'visible_to_clinic' => 'boolean',
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

    public function overrides(): HasMany
    {
        return $this->hasMany(ClinicPortalCredentialOverride::class);
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PortalCredentialPasswordHistory::class)
            ->latest('created_at')
            ->latest('id');
    }

    public function overrideForClinic(?int $clinicId): ?ClinicPortalCredentialOverride
    {
        if (! $clinicId) {
            return null;
        }

        if ($this->relationLoaded('overrides')) {
            return $this->overrides->firstWhere('clinic_id', $clinicId);
        }

        return $this->overrides()
            ->where('clinic_id', $clinicId)
            ->first();
    }

    public function effectiveAttributesForClinic(?int $clinicId): array
    {
        $override = $this->overrideForClinic($clinicId);

        return [
            'portal_name' => $override?->portal_name ?: $this->portal_name,
            'portal_category' => $override?->portal_category ?: $this->portal_category,
            'login_url' => $override?->login_url ?: $this->login_url,
            'username' => $override?->username ?: $this->username,
            'password' => $override?->password ?: $this->password,
            'account_reference' => $override?->account_reference ?: $this->account_reference,
            'support_contact' => $override?->support_contact ?: $this->support_contact,
            'registration_qa_notes' => $override?->registration_qa_notes ?: $this->registration_qa_notes,
            'general_notes' => $override?->general_notes ?: $this->general_notes,
            'notes' => $override?->notes ?: $this->notes,
            'mfa_required' => $override?->mfa_required ?? $this->mfa_required,
            'mfa_method' => $override?->mfa_method ?: $this->mfa_method,
            'is_active' => $override?->is_active ?? $this->is_active,
            'visible_to_clinic' => (bool) $this->visible_to_clinic,
            'has_override' => (bool) $override,
        ];
    }

    public function isVisibleToClinicPanel(): bool
    {
        return (bool) $this->visible_to_clinic;
    }

    public static function maskSecret(?string $value): string
    {
        if (blank($value)) {
            return '-';
        }

        $string = (string) $value;

        if (strlen($string) <= 4) {
            return str_repeat('*', strlen($string));
        }

        return substr($string, 0, 2) . str_repeat('*', max(strlen($string) - 4, 2)) . substr($string, -2);
    }

    protected static function booted(): void
    {
        static::updating(function (self $credential): void {
            if (! $credential->isDirty('password')) {
                return;
            }

            $rawOriginalPassword = $credential->getRawOriginal('password');

            if (blank($rawOriginalPassword)) {
                return;
            }

            try {
                $credential->passwordBeforeUpdate = Crypt::decryptString($rawOriginalPassword);
            } catch (\Throwable) {
                $credential->passwordBeforeUpdate = null;
            }
        });

        static::updated(function (self $credential): void {
            if (! $credential->wasChanged('password')) {
                return;
            }

            if (filled($credential->passwordBeforeUpdate)) {
                $credential->passwordHistories()->create([
                    'organization_id' => $credential->organization_id,
                    'clinic_id' => $credential->clinic_id,
                    'changed_by_user_id' => auth()->id(),
                    'changed_by_name' => auth()->user()?->name,
                    'password_snapshot' => $credential->passwordBeforeUpdate,
                ]);

                $idsToKeep = $credential->passwordHistories()
                    ->limit(5)
                    ->pluck('id');

                $credential->passwordHistories()
                    ->whereNotIn('id', $idsToKeep)
                    ->delete();
            }

            $credential->passwordBeforeUpdate = null;
        });
    }
}
