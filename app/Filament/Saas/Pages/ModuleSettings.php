<?php

namespace App\Filament\Saas\Pages;

use App\Filament\Shared\Pages\ModuleSettingsPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ModuleSettings extends ModuleSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Module Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'module-settings';

    protected string $view = 'filament.shared.pages.module-settings';

    protected static function panelKey(): string
    {
        return 'saas';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->status
            && auth()->user()?->hasRole('saas_admin');
    }
}
