<?php

namespace App\Filament\Saas\Pages;

use App\Models\SaasSetting;
use App\Support\SaasMailSettings;
use App\Support\SaasNotifications;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

class NotificationCentre extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Notification Centre';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Notification Centre';

    protected static ?string $slug = 'notification-centre';

    protected string $view = 'filament.saas.pages.notification-centre';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessSaasModule('settings') ?? false;
    }

    public function mount(): void
    {
        $settings = SaasSetting::current();

        $this->form->fill([
            ...$settings->toArray(),
            'email_password' => null,
            'test_email_recipient' => auth()->user()?->email ?? $settings->support_email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('In-App Notifications')
                    ->description('Control which events create persistent notifications inside the SaaS panel.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('notify_database_on_organization_onboarded')
                                    ->label('Organization onboarded')
                                    ->default(true),
                                Toggle::make('notify_database_on_incomplete_onboarding')
                                    ->label('Incomplete onboarding')
                                    ->default(true),
                                Toggle::make('notify_database_on_settings_updated')
                                    ->label('Settings updated')
                                    ->default(true),
                                Toggle::make('notify_database_on_user_created')
                                    ->label('User created')
                                    ->default(true),
                                Toggle::make('notify_database_on_user_updated')
                                    ->label('User updated')
                                    ->default(true),
                                Toggle::make('notify_database_on_user_deleted')
                                    ->label('User deleted')
                                    ->default(true),
                                Toggle::make('notify_database_on_invoice_created')
                                    ->label('Invoice created')
                                    ->default(true),
                                Toggle::make('notify_database_on_invoice_updated')
                                    ->label('Invoice updated')
                                    ->default(true),
                                Toggle::make('notify_database_on_invoice_deleted')
                                    ->label('Invoice deleted')
                                    ->default(true),
                                Toggle::make('notify_database_on_invoice_sent')
                                    ->label('Invoice sent')
                                    ->default(true),
                            ]),
                    ]),
                Section::make('Email Delivery')
                    ->description('Configure the outbound email channel used by notification events and test sends.')
                    ->schema([
                        Toggle::make('email_enabled')
                            ->label('Enable email notifications')
                            ->live()
                            ->default(false),
                        Select::make('email_mailer')
                            ->label('Mailer')
                            ->options([
                                'smtp' => 'SMTP',
                                'log' => 'Log',
                            ])
                            ->default('smtp')
                            ->required()
                            ->live()
                            ->native(false),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('email_host')
                                    ->label('SMTP host')
                                    ->maxLength(255)
                                    ->visible(fn (Get $get): bool => ($get('email_enabled') ?? false) && $get('email_mailer') === 'smtp')
                                    ->required(fn (Get $get): bool => ($get('email_enabled') ?? false) && $get('email_mailer') === 'smtp'),
                                TextInput::make('email_port')
                                    ->label('SMTP port')
                                    ->numeric()
                                    ->visible(fn (Get $get): bool => ($get('email_enabled') ?? false) && $get('email_mailer') === 'smtp')
                                    ->required(fn (Get $get): bool => ($get('email_enabled') ?? false) && $get('email_mailer') === 'smtp'),
                                TextInput::make('email_username')
                                    ->label('SMTP username')
                                    ->maxLength(255)
                                    ->visible(fn (Get $get): bool => ($get('email_enabled') ?? false) && $get('email_mailer') === 'smtp'),
                                TextInput::make('email_password')
                                    ->label('SMTP password')
                                    ->password()
                                    ->revealable()
                                    ->helperText('Leave blank to keep the currently saved password.')
                                    ->maxLength(255)
                                    ->visible(fn (Get $get): bool => ($get('email_enabled') ?? false) && $get('email_mailer') === 'smtp'),
                                Select::make('email_encryption')
                                    ->label('Encryption')
                                    ->options([
                                        'tls' => 'TLS',
                                        'ssl' => 'SSL',
                                        '' => 'None',
                                    ])
                                    ->default('tls')
                                    ->visible(fn (Get $get): bool => ($get('email_enabled') ?? false) && $get('email_mailer') === 'smtp')
                                    ->native(false),
                                TextInput::make('email_from_address')
                                    ->label('From email')
                                    ->email()
                                    ->maxLength(255)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled'))
                                    ->required(fn (Get $get): bool => (bool) $get('email_enabled')),
                                TextInput::make('email_from_name')
                                    ->label('From name')
                                    ->maxLength(255)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled')),
                            ]),
                    ]),
                Section::make('Email Events')
                    ->description('Choose which SaaS events should also send email when email notifications are enabled.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('email_on_organization_onboarded')
                                    ->label('Organization onboarded')
                                    ->default(false)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled')),
                                Toggle::make('email_on_incomplete_onboarding')
                                    ->label('Incomplete onboarding')
                                    ->default(false)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled')),
                                Toggle::make('email_on_settings_updated')
                                    ->label('Settings updated')
                                    ->default(false)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled')),
                                Toggle::make('email_on_user_created')
                                    ->label('User created')
                                    ->default(false)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled')),
                                Toggle::make('email_on_user_updated')
                                    ->label('User updated')
                                    ->default(false)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled')),
                                Toggle::make('email_on_user_deleted')
                                    ->label('User deleted')
                                    ->default(false)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled')),
                                Toggle::make('email_send_user_verification')
                                    ->label('User verification email')
                                    ->default(true)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled')),
                                Toggle::make('email_on_invoice_created')
                                    ->label('Invoice created')
                                    ->default(false)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled')),
                                Toggle::make('email_on_invoice_updated')
                                    ->label('Invoice updated')
                                    ->default(false)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled')),
                                Toggle::make('email_on_invoice_deleted')
                                    ->label('Invoice deleted')
                                    ->default(false)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled')),
                                Toggle::make('email_on_invoice_sent')
                                    ->label('Invoice sent')
                                    ->default(true)
                                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled')),
                            ]),
                    ]),
                Section::make('Test Email')
                    ->description('Send a quick test with the current form values before you rely on email alerts.')
                    ->schema([
                        TextInput::make('test_email_recipient')
                            ->label('Test recipient')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestEmail')
                ->label('Send test email')
                ->action('sendTestEmail'),
            Action::make('save')
                ->label('Save notification centre')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $settings = SaasSetting::current();
        $data = $this->form->getState();

        $persistedPassword = $settings->email_password;
        $newPassword = $data['email_password'] ?? null;

        unset($data['test_email_recipient'], $data['email_password']);

        if (filled($newPassword)) {
            $data['email_password'] = $newPassword;
        } elseif (filled($persistedPassword)) {
            $data['email_password'] = $persistedPassword;
        }

        $settings->update($data);
        SaasNotifications::settingsUpdated(auth()->user());

        Notification::make()
            ->title('Notification centre saved')
            ->body('In-app and email notification settings have been updated.')
            ->success()
            ->send();
    }

    public function sendTestEmail(): void
    {
        $settings = SaasSetting::current();
        $state = $this->form->getState();
        $recipient = $state['test_email_recipient'] ?? auth()->user()?->email;

        if (! filled($recipient)) {
            Notification::make()
                ->title('Test email not sent')
                ->body('Enter a valid recipient email address first.')
                ->danger()
                ->send();

            return;
        }

        $state['email_password'] = filled($state['email_password'] ?? null)
            ? $state['email_password']
            : $settings->email_password;

        if (! SaasMailSettings::canSend($state)) {
            Notification::make()
                ->title('Email setup is incomplete')
                ->body('Enable email notifications and complete the required mail settings before sending a test.')
                ->danger()
                ->send();

            return;
        }

        try {
            SaasMailSettings::sendTestEmail($state, $recipient);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Test email failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Test email sent')
            ->body("A test email was sent to {$recipient}.")
            ->success()
            ->send();
    }
}
