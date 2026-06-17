<?php

namespace App\Filament\Admin\Pages;

use App\Models\UserMailbox;
use App\Support\UserMailboxService;
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
use UnitEnum;

class UserMailboxSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected const PROVIDER_MEDITYA = 'meditya';

    protected const PROVIDER_CUSTOM = 'custom';

    protected const MEDITYA_HOST = 'mail.medityaglobalservices.com';

    protected const MEDITYA_PROVIDER_LABEL = 'Meditya MailBox';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Mailbox Settings';

    protected static ?int $navigationSort = 98;

    protected static ?string $title = '';

    protected static ?string $slug = 'mailbox-settings';

    protected string $view = 'filament.admin.pages.user-mailbox-settings';

    public ?array $data = [];

    protected ?UserMailbox $mailboxRecord = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessVerificationWorkspace() ?? false;
    }

    public function mount(): void
    {
        $this->mailboxRecord = $this->getMailboxRecord();
        $this->form->fill($this->formDefaults());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Test Connection')
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->action('testConnection'),
            Action::make('save')
                ->label('Save Mailbox Settings')
                ->action('save'),
        ];
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Mailbox Connection')
                    ->description('Choose Meditya MailBox for fixed server settings, or Custom Mail Server when the mailbox is hosted somewhere else.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('enabled')
                                ->label('Enable mailbox')
                                ->default(true),
                            Select::make('mailbox_provider_mode')
                                ->label('Mailbox type')
                                ->options([
                                    self::PROVIDER_MEDITYA => 'Meditya MailBox',
                                    self::PROVIDER_CUSTOM => 'Custom Mail Server',
                                ])
                                ->default(self::PROVIDER_MEDITYA)
                                ->native(false)
                                ->live()
                                ->required()
                                ->helperText('Meditya MailBox only needs the mailbox user ID and password.'),
                            TextInput::make('imap_username')
                                ->label('Mailbox user ID')
                                ->email()
                                ->required(),
                            TextInput::make('imap_password')
                                ->label('Mailbox password')
                                ->password()
                                ->revealable()
                                ->placeholder('Leave blank to keep the saved password'),
                        ]),
                    ]),
                Section::make('Custom Server Details')
                    ->description('Use this only when the mailbox is not hosted on the Meditya mail server.')
                    ->visible(fn (Get $get): bool => $get('mailbox_provider_mode') === self::PROVIDER_CUSTOM)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('provider_label')
                                ->label('Provider label')
                                ->placeholder('Gmail, Outlook, Zoho, Custom'),
                            Toggle::make('imap_validate_certificate')
                                ->label('Validate IMAP certificate')
                                ->default(false),
                            TextInput::make('imap_host')
                                ->label('IMAP host')
                                ->required(fn (Get $get): bool => $get('mailbox_provider_mode') === self::PROVIDER_CUSTOM)
                                ->default(self::MEDITYA_HOST),
                            TextInput::make('imap_port')
                                ->label('IMAP port')
                                ->required(fn (Get $get): bool => $get('mailbox_provider_mode') === self::PROVIDER_CUSTOM)
                                ->numeric()
                                ->default(993),
                            Select::make('imap_encryption')
                                ->label('IMAP encryption')
                                ->options([
                                    'ssl' => 'SSL',
                                    'tls' => 'TLS',
                                    'none' => 'None',
                                ])
                                ->default('ssl')
                                ->native(false),
                            TextInput::make('inbox_folder')
                                ->label('Inbox folder')
                                ->default('INBOX'),
                            TextInput::make('spam_folder')
                                ->label('Spam folder')
                                ->default('INBOX.Spam'),
                            TextInput::make('sent_folder')
                                ->label('Sent folder')
                                ->default('INBOX.Sent'),
                        ]),
                    ]),
                Section::make('Outgoing Email')
                    ->description('Custom outgoing email settings. Leave username/password blank to reuse the mailbox login.')
                    ->visible(fn (Get $get): bool => $get('mailbox_provider_mode') === self::PROVIDER_CUSTOM)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('smtp_host')
                                ->label('SMTP host')
                                ->default(self::MEDITYA_HOST),
                            TextInput::make('smtp_port')
                                ->label('SMTP port')
                                ->numeric()
                                ->default(465),
                            Select::make('smtp_encryption')
                                ->label('SMTP encryption')
                                ->options([
                                    'ssl' => 'SSL',
                                    'tls' => 'TLS',
                                    'none' => 'None',
                                ])
                                ->default('ssl')
                                ->native(false),
                            TextInput::make('smtp_username')
                                ->label('SMTP user ID')
                                ->placeholder('Leave blank to reuse the mailbox user ID'),
                            TextInput::make('smtp_password')
                                ->label('SMTP password')
                                ->password()
                                ->revealable()
                                ->placeholder('Leave blank to reuse the mailbox password'),
                            TextInput::make('from_name')
                                ->label('From name')
                                ->default(auth()->user()?->name),
                            TextInput::make('from_address')
                                ->label('From address')
                                ->email()
                                ->placeholder('Leave blank to reuse the mailbox user ID'),
                        ]),
                    ]),
                Section::make('Mailbox Preferences')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('attachment_limit_mb')
                                ->label('Attachment size limit (MB)')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->default(25)
                                ->helperText('Maximum allowed size for each attachment in compose mail.'),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->normalizeMailboxState($this->form->getState());
        $existing = $this->getMailboxRecord();

        if (
            ($state['provider_label'] ?? null) === self::MEDITYA_PROVIDER_LABEL
            && blank($state['smtp_password'] ?? null)
            && filled($existing->imap_password)
        ) {
            $state['smtp_password'] = $existing->imap_password;
        }

        if (blank($state['imap_password'] ?? null) && blank($existing->imap_password)) {
            Notification::make()
                ->title('IMAP password required')
                ->body('Add the mailbox password or app password before saving the mailbox.')
                ->danger()
                ->send();

            return;
        }

        if (blank($state['smtp_password'] ?? null) && blank($existing->smtp_password) && blank($existing->imap_password) && blank($state['imap_password'] ?? null)) {
            Notification::make()
                ->title('Outgoing password required')
                ->body('Add an SMTP password or keep the mailbox password filled so outgoing email can use it.')
                ->danger()
                ->send();

            return;
        }

        if (blank($state['imap_password'] ?? null)) {
            unset($state['imap_password']);
        }

        if (blank($state['smtp_password'] ?? null)) {
            unset($state['smtp_password']);
        }

        $existing->update($state);
        $this->mailboxRecord = $existing->fresh();
        $this->form->fill($this->formDefaults());

        Notification::make()
            ->title('Mailbox settings saved')
            ->body('Your universal mailbox connection is updated successfully.')
            ->success()
            ->send();
    }

    public function testConnection(UserMailboxService $service): void
    {
        $state = $this->normalizeMailboxState($this->form->getState());
        $mailbox = $this->getMailboxRecord();

        if (blank($state['imap_password'] ?? null)) {
            unset($state['imap_password']);
        }

        if (blank($state['smtp_password'] ?? null)) {
            unset($state['smtp_password']);
        }

        $mailbox->fill($state);

        $result = $service->testConnection($mailbox);

        Notification::make()
            ->title($result['ok'] ? 'Mailbox ready' : 'Connection failed')
            ->body($result['message'])
            ->{$result['ok'] ? 'success' : 'danger'}()
            ->send();
    }

    public function getConnectionStatus(): array
    {
        $service = app(UserMailboxService::class);
        $mailbox = $this->getMailboxRecord();

        if (! $service->imapAvailable()) {
            return [
                'tone' => 'danger',
                'label' => 'IMAP extension missing',
                'description' => 'This server cannot open mailbox folders until the PHP IMAP extension is enabled.',
            ];
        }

        if (! $service->isConfigured($mailbox)) {
            return [
                'tone' => 'warning',
                'label' => 'Mailbox not configured',
                'description' => 'Add your mailbox user ID and password to start live inbox access.',
            ];
        }

        if (! $mailbox->enabled) {
            return [
                'tone' => 'warning',
                'label' => 'Mailbox disabled',
                'description' => 'The mailbox is saved, but it is currently disabled.',
            ];
        }

        return [
            'tone' => 'success',
            'label' => 'Mailbox ready',
            'description' => 'Your mailbox is ready for live inbox and direct sending.',
        ];
    }

    protected function getMailboxRecord(): UserMailbox
    {
        return $this->mailboxRecord ??= app(UserMailboxService::class)->mailbox(auth()->user(), createIfMissing: true);
    }

    protected function formDefaults(): array
    {
        $mailbox = $this->getMailboxRecord();

        $state = $mailbox->only([
            'enabled',
            'provider_label',
            'imap_host',
            'imap_port',
            'imap_encryption',
            'imap_validate_certificate',
            'imap_username',
            'inbox_folder',
            'spam_folder',
            'sent_folder',
            'smtp_host',
            'smtp_port',
            'smtp_encryption',
            'smtp_username',
            'from_name',
            'from_address',
            'attachment_limit_mb',
        ]);

        $state['mailbox_provider_mode'] = $this->detectProviderMode($mailbox);
        $state['imap_password'] = '';
        $state['smtp_password'] = '';

        return $state;
    }

    protected function normalizeMailboxState(array $state): array
    {
        $mode = $state['mailbox_provider_mode'] ?? self::PROVIDER_MEDITYA;
        unset($state['mailbox_provider_mode']);

        if ($mode === self::PROVIDER_MEDITYA) {
            $username = $state['imap_username'] ?? null;

            return array_merge($state, [
                'provider_label' => self::MEDITYA_PROVIDER_LABEL,
                'imap_host' => self::MEDITYA_HOST,
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'imap_validate_certificate' => false,
                'inbox_folder' => 'INBOX',
                'spam_folder' => 'INBOX.Spam',
                'sent_folder' => 'INBOX.Sent',
                'smtp_host' => self::MEDITYA_HOST,
                'smtp_port' => 465,
                'smtp_encryption' => 'ssl',
                'smtp_username' => $username,
                'smtp_password' => $state['imap_password'] ?? null,
                'from_address' => filled($state['from_address'] ?? null) ? $state['from_address'] : $username,
            ]);
        }

        return $state;
    }

    protected function detectProviderMode(UserMailbox $mailbox): string
    {
        $isMeditya = $mailbox->imap_host === self::MEDITYA_HOST
            && (int) $mailbox->imap_port === 993
            && strtolower((string) $mailbox->imap_encryption) === 'ssl'
            && ($mailbox->smtp_host === self::MEDITYA_HOST || blank($mailbox->smtp_host))
            && ((int) $mailbox->smtp_port === 465 || blank($mailbox->smtp_port));

        return $isMeditya ? self::PROVIDER_MEDITYA : self::PROVIDER_CUSTOM;
    }
}
