<?php

namespace App\Filament\Saas\Pages;

use App\Services\Eligibility\EligibilityProviderCatalog;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class EligibilityConnections extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $title = 'Eligibility Connections';

    protected static ?string $slug = 'eligibility-connections';

    protected static ?int $navigationSort = 21;

    protected string $view = 'filament.saas.pages.eligibility-connections';

    public static function canAccess(): bool
    {
        return EligibilityConnectionSettings::canAccess();
    }

    public function setProviderEnabled(string $provider, bool $enabled): void
    {
        abort_unless(static::canAccess(), 403);
        app(EligibilityProviderCatalog::class)->setEnabled(auth()->user(), $provider, $enabled);
    }

    public function providerRows(): array
    {
        abort_unless(static::canAccess(), 403);

        return app(EligibilityProviderCatalog::class)->rows();
    }
}
