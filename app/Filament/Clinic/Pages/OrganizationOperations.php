<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Clinic\Pages\Concerns\InteractsWithOrganizationOperationsWorkspace;
use App\Support\ClinicWorkspace;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class OrganizationOperations extends Page
{
    use InteractsWithOrganizationOperationsWorkspace;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Clinic Data';

    protected static ?string $navigationLabel = 'Clinic Profile';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = '';

    protected static ?string $slug = 'organization-operations';

    protected string $view = 'filament.clinic.pages.organization-dashboard';

    public static function canAccess(): bool
    {
        return ClinicWorkspace::canUse(ClinicWorkspace::VERIFICATION, ClinicWorkspace::clinicForUser());
    }
}
