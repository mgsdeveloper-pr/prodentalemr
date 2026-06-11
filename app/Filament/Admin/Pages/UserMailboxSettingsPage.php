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
use UnitEnum;

class UserMailboxSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

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
                    ->description('Connect your own mailbox for live receive and send access. Default Meditya server details are prefilled, but you can replace them for another provider.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('enabled')
                                ->label('Enable mailbox')
                                ->default(true),
                            Toggle::make('imap_validate_certificate')
                                ->label('Validate IMAP certificate')
                                ->default(false),
                            TextInput::make('provider_label')
                                ->label('Provider label')
                                ->placeholder('Meditya Mail, Gmail, Outlook, Zoho, Custom'),
                            TextInput::make('imap_host')
                                ->label('IMAP host')
                                ->required()
                                ->default('mail.medityaglobalservices.com'),
                            TextInput::make('imap_port')
                                ->label('IMAP port')
                                ->required()
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
                            TextInput::make('imap_username')
                                ->label('Mailbox user ID')
                                ->required(),
                            TextInput::make('imap_password')
                                ->label('Mailbox password')
                                ->password()
                                ->revealable()
                                ->placeholder('Leave blank to keep the saved password'),
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
                    ->description('Use the same mailbox for sending, or replace these details if your outgoing server is different.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('smtp_host')
                                ->label('SMTP host')
                                ->default('mail.medityaglobalservices.com'),
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
        $state = $this->form->getState();
        $existing = $this->getMailboxRecord();

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
        $state = $this->form->getState();
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

        $state['imap_password'] = '';
        $state['smtp_password'] = '';

        return $state;
    }
}
