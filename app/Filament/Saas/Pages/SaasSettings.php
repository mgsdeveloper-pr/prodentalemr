<?php

namespace App\Filament\Saas\Pages;

use App\Models\SaasSetting;
use App\Support\SaasNotifications;
use App\Support\UsTimezoneOptions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SaasSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'SaaS Settings';

    protected static ?string $slug = 'settings';

    protected string $view = 'filament.saas.pages.saas-settings';

    public ?array $data = [];

    protected SaasSetting $settings;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessSaasModule('settings') ?? false;
    }

    public function mount(): void
    {
        $this->settings = SaasSetting::current();

        $this->form->fill($this->settings->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Platform')
                    ->description('Core platform identity and support contacts for the SaaS admin side.')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('platform_name')
                            ->label('Platform name')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('company_name')
                            ->label('Company name')
                            ->maxLength(255),
                        \Filament\Forms\Components\FileUpload::make('logo_path')
                            ->label('Company logo')
                            ->disk('branding')
                            ->directory('branding')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->helperText('Upload a JPG, PNG, or WebP logo for the SaaS panel header and login screen.'),
                        \Filament\Forms\Components\TextInput::make('support_email')
                            ->label('Support email')
                            ->email()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('support_phone')
                            ->label('Support phone')
                            ->tel()
                            ->maxLength(255),
                        \Filament\Forms\Components\Textarea::make('address')
                            ->label('Business address')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Defaults')
                    ->description('Default values for new organizations and clinics. These can still be changed case by case.')
                    ->schema([
                        Select::make('default_country')
                            ->label('Default country')
                            ->options(['USA' => 'USA'])
                            ->default('USA')
                            ->required()
                            ->native(false),
                        Select::make('default_timezone')
                            ->label('Default timezone')
                            ->options(UsTimezoneOptions::options())
                            ->searchable()
                            ->preload()
                            ->default('America/New_York')
                            ->required()
                            ->native(false),
                        \Filament\Forms\Components\TextInput::make('default_currency')
                            ->label('Default currency')
                            ->default('USD')
                            ->required()
                            ->maxLength(3)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper($state) : $state),
                        Toggle::make('maintenance_mode')
                            ->label('Maintenance mode')
                            ->helperText('Keeps the setting available for future platform-wide safeguards.')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->settings->update($data);

        SaasNotifications::settingsUpdated(auth()->user());

        Notification::make()
            ->title('Settings saved')
            ->body('SaaS settings have been updated successfully.')
            ->success()
            ->send();
    }
}
