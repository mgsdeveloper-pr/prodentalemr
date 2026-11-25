<?php

namespace App\Filament\Saas\Pages\Concerns;

use App\Filament\Clinic\Pages\Concerns\InteractsWithVerificationQuestionLibraryOrdering;
use App\Models\Clinic;
use App\Support\AdminClinicScope;

trait InteractsWithAdminVerificationQuestionLibraryOrdering
{
    use InteractsWithVerificationQuestionLibraryOrdering;

    protected function arrangementClinic(): ?Clinic
    {
        return AdminClinicScope::selectedClinic();
    }
}
