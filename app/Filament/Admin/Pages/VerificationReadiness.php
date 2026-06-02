<?php

namespace App\Filament\Admin\Pages;

use App\Support\VerificationReadinessReport;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VerificationReadiness extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Insurance Verification';

    protected static ?string $navigationLabel = 'Verification Readiness';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Verification Readiness';

    protected static ?string $slug = 'verification-readiness';

    protected string $view = 'filament.admin.pages.verification-readiness';

    public static function canAccess(): bool
    {
        return (bool) (
            auth()->user()?->canAccessVerificationModule('settings')
            || auth()->user()?->canAccessSaasRevenueOperations()
        );
    }

    public function getSummary(): array
    {
        return VerificationReadinessReport::summary();
    }

    public function getSections(): array
    {
        return VerificationReadinessReport::sections();
    }
}
