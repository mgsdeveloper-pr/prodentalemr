<?php

namespace App\Filament\Admin\Pages;

use App\Models\Clinic;
use App\Support\AdminClinicScope;

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
        return VerificationGeneralSettings::getUrl(panel: 'admin');
    }
}
