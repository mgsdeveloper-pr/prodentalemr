<?php

namespace App\Filament\Clinic\Pages;

use App\Models\Clinic;
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
        return VerificationSettings::getUrl(['section' => 'template-management'], panel: 'clinic');
    }
}
