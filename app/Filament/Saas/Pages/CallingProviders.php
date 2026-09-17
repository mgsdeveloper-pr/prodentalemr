<?php

namespace App\Filament\Saas\Pages;

use App\Models\SaasSetting;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

class CallingProviders extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|UnitEnum|null $navigationGroup = 'Calling';

    protected static ?string $title = 'Calling Providers';

    protected static ?string $slug = 'calling-providers';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.saas.pages.calling-providers';

    public static function canAccess(): bool
    {
        return (auth()->user()?->isSaasAdmin() ?? false)
            && (auth()->user()?->canAccessSaasModule('calling') ?? false);
    }

    public function storageReady(): bool
    {
        return Schema::hasColumn('saas_settings', 'twilio_option_enabled');
    }

    public function twilioEnabled(): bool
    {
        abort_unless(static::canAccess(), 403);

        return $this->storageReady() && (bool) SaasSetting::current()->twilio_option_enabled;
    }

    public function setTwilioEnabled(bool $enabled): void
    {
        abort_unless(static::canAccess() && auth()->user()->canPerformSaasModuleAction('calling', 'update'), 403);
        abort_unless($this->storageReady(), 503);
        $settings = SaasSetting::current();
        $settings->twilio_option_enabled = $enabled;
        $settings->save();
        Notification::make()->title($enabled ? 'Twilio option enabled' : 'Twilio option disabled')->success()->send();
    }
}
