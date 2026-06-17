<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Admin\Pages\VerificationClinicAssignments as AdminVerificationClinicAssignments;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VerificationClinicAssignments extends AdminVerificationClinicAssignments
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Access Management';

    protected static ?string $navigationLabel = 'Assign Clinic';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'assign-clinic';

    public function closeUrl(): string
    {
        return Dashboard::getUrl();
    }
}
