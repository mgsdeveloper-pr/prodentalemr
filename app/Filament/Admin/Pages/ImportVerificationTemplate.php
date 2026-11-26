<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\ClinicTemplates\ClinicTemplateResource;
use App\Models\Clinic;
use App\Models\VerificationTemplateVersion;
use App\Support\AdminClinicScope;
use App\Support\ClinicPanelScope;

class ImportVerificationTemplate extends \App\Filament\Shared\Pages\ImportVerificationTemplate
{
    protected static function scopedClinic(): ?Clinic
    {
        return AdminClinicScope::selectedClinic();
    }

    protected static function isMasterImport(): bool
    {
        return false;
    }

    public function builderUrl(): string
    {
        if ($this->canOpenDelegatedBuilder()) {
            return ClinicTemplateResource::getUrl('index', ['version' => $this->createdVersionId], panel: 'admin');
        }

        if ($this->canOpenClinicBuilder()) {
            return route('clinic.clinic-scope', [
                'clinic_id' => static::scopedClinic()->id,
                'template_version_id' => $this->createdVersionId,
            ]);
        }

        return VerificationGeneralSettings::getUrl(panel: 'admin');
    }

    public function builderLinkLabel(): string
    {
        return $this->canOpenClinicBuilder() || $this->canOpenDelegatedBuilder()
            ? parent::builderLinkLabel() : 'Back to Verification Settings';
    }

    private function canOpenDelegatedBuilder(): bool
    {
        $clinic = static::scopedClinic();

        return $this->createdVersionId !== null
            && $clinic !== null
            && ! auth()->user()?->isSaasAdmin()
            && ClinicTemplateResource::canAccess()
            && VerificationTemplateVersion::query()
                ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
                ->where('clinic_id', $clinic->id)
                ->whereKey($this->createdVersionId)
                ->exists();
    }

    private function canOpenClinicBuilder(): bool
    {
        $clinic = static::scopedClinic();
        $user = auth()->user();

        return $this->createdVersionId !== null
            && $clinic?->hasActiveVerificationServices()
            && ($user?->isSaasAdmin() || ($user?->canAccessClinicWorkspace()
                && \App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource::canAccess()))
            && array_key_exists($clinic->id, ClinicPanelScope::clinicOptions());
    }
}
