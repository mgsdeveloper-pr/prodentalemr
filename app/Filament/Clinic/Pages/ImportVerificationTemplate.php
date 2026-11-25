<?php

namespace App\Filament\Clinic\Pages;

use App\Models\Clinic;
use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Support\ClinicPanelScope;

class ImportVerificationTemplate extends \App\Filament\Shared\Pages\ImportVerificationTemplate
{
    protected static function scopedClinic(): ?Clinic
    {
        return ClinicPanelScope::selectedClinic();
    }

    protected static function isMasterImport(): bool
    {
        return false;
    }

    public function builderUrl(): string
    {
        if ($this->createdVersionId !== null && VerificationQuestionResource::canAccess()) {
            return VerificationQuestionResource::getUrl('index', ['version' => $this->createdVersionId, 'form' => $this->formType], panel: 'clinic');
        }

        return VerificationSettings::getUrl(['section' => 'template-management'], panel: 'clinic');
    }

    public function builderLinkLabel(): string
    {
        return $this->createdVersionId !== null && VerificationQuestionResource::canAccess()
            ? parent::builderLinkLabel()
            : 'Template Library';
    }
}
