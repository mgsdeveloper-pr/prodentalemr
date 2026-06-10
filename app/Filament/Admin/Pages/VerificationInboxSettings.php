<?php

namespace App\Filament\Admin\Pages;

use App\Models\VerificationInboxMailbox;
use App\Support\VerificationInboxService;
use App\Support\AdminClinicScope;
use App\Support\VerificationSettingsNavigation;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VerificationInboxSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Alerts & Notifications';

    protected static ?string $navigationLabel = 'Inbox Configuration';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Inbox Configuration';

    protected static ?string $slug = 'inbox-configuration';

    protected string $view = 'filament.admin.pages.verification-inbox-settings';

    public ?array $data = [];

    protected ?VerificationInboxMailbox $settings = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageVerificationSettings() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        if ($this->selectedClinicId()) {
            $this->settings = $this->getSettingsRecord();
            $state = $this->settings->only($this->settingKeys());
            $state['verification_inbox_password'] = '';
            $this->form->fill($state);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Mailbox Connection')
                    ->description('Connect the verification mailbox used for OTP codes, portal registration emails, and payer notices for the selected clinic.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('verification_inbox_enabled')
                                ->label('Enable shared inbox sync')
                                ->default(false),
                            Toggle::make('verification_inbox_validate_certificate')
                                ->label('Validate mailbox certificate')
                                ->default(false),
                            TextInput::make('verification_inbox_provider')
                                ->label('Provider label')
                                ->placeholder('Gmail, Outlook, Zoho, Custom IMAP'),
                            TextInput::make('verification_inbox_host')
                                ->label('IMAP host')
                                ->placeholder('imap.gmail.com'),
                            TextInput::make('verification_inbox_port')
                                ->label('Port')
                                ->numeric()
                                ->default(993),
                            Select::make('verification_inbox_protocol')
                                ->label('Protocol')
                                ->options(['imap' => 'IMAP'])
                                ->default('imap')
                                ->native(false),
                            Select::make('verification_inbox_encryption')
                                ->label('Encryption')
                                ->options([
                                    'ssl' => 'SSL',
                                    'tls' => 'TLS',
                                    'none' => 'None',
                                ])
                                ->default('ssl')
                                ->native(false),
                            TextInput::make('verification_inbox_username')
                                ->label('Mailbox username')
                                ->placeholder('shared-mailbox@example.com'),
                            TextInput::make('verification_inbox_password')
                                ->label('Mailbox password / app password')
                                ->password()
                                ->revealable()
                                ->placeholder('Leave blank to keep the saved secret'),
                            TextInput::make('verification_inbox_folder_inbox')
                                ->label('Inbox folder')
                                ->default('INBOX'),
                            TextInput::make('verification_inbox_folder_spam')
                                ->label('Spam / Junk folder')
                                ->default('INBOX.Spam'),
                            TextInput::make('verification_inbox_sync_frequency_minutes')
                                ->label('Sync frequency (minutes)')
                                ->numeric()
                                ->default(15),
                            TextInput::make('verification_inbox_sync_window_days')
                                ->label('Sync window (days)')
                                ->numeric()
                                ->default(90),
                        ]),
                    ]),
                Section::make('Storage & Cleanup Rules')
                    ->description('Control how long messages stay in the synced mailbox and how aggressively old spam is cleaned up.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('verification_inbox_retention_mode')
                                ->label('Retention mode')
                                ->options([
                                    'none' => 'Do not delete',
                                    'days' => 'Delete after X days',
                                    'count' => 'Keep latest X emails',
                                ])
                                ->default('days')
                                ->native(false),
                            Toggle::make('verification_inbox_auto_cleanup_enabled')
                                ->label('Enable scheduled cleanup')
                                ->default(true),
                            TextInput::make('verification_inbox_retention_days')
                                ->label('Message retention (days)')
                                ->numeric()
                                ->default(90),
                            TextInput::make('verification_inbox_keep_latest_count')
                                ->label('Keep latest emails')
                                ->numeric()
                                ->default(5000),
                            TextInput::make('verification_inbox_spam_retention_days')
                                ->label('Spam retention (days)')
                                ->numeric()
                                ->default(30),
                            Toggle::make('verification_inbox_preserve_flagged')
                                ->label('Keep flagged/starred emails during cleanup')
                                ->default(true),
                        ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Test connection')
                ->action('testConnection')
                ->color('gray'),
            Action::make('syncNow')
                ->label('Sync now')
                ->action('syncNow')
                ->color('gray'),
            Action::make('runCleanup')
                ->label('Run cleanup')
                ->action('runCleanup')
                ->color('gray'),
            Action::make('save')
                ->label('Save inbox settings')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        if (! $this->ensureClinicSelected()) {
            return;
        }

        $state = $this->form->getState();
        $password = $state['verification_inbox_password'] ?? null;

        if (blank($password)) {
            unset($state['verification_inbox_password']);
        }

        $settings = $this->getSettingsRecord();
        $settings->update($state);
        $this->settings = $settings->fresh();
        $refill = $this->settings->only($this->settingKeys());
        $refill['verification_inbox_password'] = '';
        $this->form->fill($refill);

        Notification::make()
            ->title('Inbox configuration saved')
            ->body('Mailbox connection details, sync behavior, and cleanup rules have been updated for the selected clinic.')
            ->success()
            ->send();
    }

    public function testConnection(VerificationInboxService $service): void
    {
        if (! $this->ensureClinicSelected()) {
            return;
        }

        $this->saveDraftState();
        $result = $service->testConnection($this->selectedClinicId());

        Notification::make()
            ->title($result['ok'] ? 'Mailbox connection verified' : 'Mailbox connection failed')
            ->body($result['message'])
            ->{$result['ok'] ? 'success' : 'danger'}()
            ->send();
    }

    public function syncNow(VerificationInboxService $service): void
    {
        if (! $this->ensureClinicSelected()) {
            return;
        }

        $this->saveDraftState();
        $result = $service->sync(force: true, clinicId: $this->selectedClinicId());

        Notification::make()
            ->title($result['ok'] ? 'Inbox sync finished' : 'Inbox sync failed')
            ->body($result['message'])
            ->{$result['ok'] ? 'success' : 'danger'}()
            ->send();

        $this->settings = $this->getSettingsRecord()->fresh();
    }

    public function runCleanup(VerificationInboxService $service): void
    {
        if (! $this->ensureClinicSelected()) {
            return;
        }

        $this->saveDraftState();
        $result = $service->cleanup($this->selectedClinicId());

        Notification::make()
            ->title($result['ok'] ? 'Inbox cleanup finished' : 'Inbox cleanup skipped')
            ->body($result['message'])
            ->{$result['ok'] ? 'success' : 'warning'}()
            ->send();

        $this->settings = $this->getSettingsRecord()->fresh();
    }

    public function getVerificationNavItems(): array
    {
        return VerificationSettingsNavigation::items();
    }

    public function getStorageSummary(): array
    {
        $clinicId = $this->selectedClinicId();

        return [
            'messages' => $clinicId ? \App\Models\VerificationInboxMessage::query()->where('clinic_id', $clinicId)->count() : 0,
            'attachments' => $clinicId
                ? \App\Models\VerificationInboxAttachment::query()
                    ->whereHas('message', fn ($query) => $query->where('clinic_id', $clinicId))
                    ->count()
                : 0,
            'last_sync' => $clinicId ? ($this->getSettingsRecord()->verification_inbox_last_synced_at?->format('d M Y, h:i A') ?? 'Not synced yet') : 'Select clinic first',
            'last_cleanup' => $clinicId ? ($this->getSettingsRecord()->verification_inbox_last_cleanup_at?->format('d M Y, h:i A') ?? 'Not cleaned yet') : 'Select clinic first',
        ];
    }

    protected function saveDraftState(): void
    {
        $state = $this->form->getState();
        $password = $state['verification_inbox_password'] ?? null;

        if (blank($password)) {
            unset($state['verification_inbox_password']);
        }

        $settings = $this->getSettingsRecord();
        $settings->update($state);
        $this->settings = $settings->fresh();
    }

    public function getSelectedClinicLabel(): string
    {
        return AdminClinicScope::selectedClinic()?->clinic_name ?? 'Select clinic in workspace';
    }

    protected function getSettingsRecord(): VerificationInboxMailbox
    {
        return $this->settings ??= app(VerificationInboxService::class)->mailbox($this->selectedClinicId(), createIfMissing: true);
    }

    protected function settingKeys(): array
    {
        return [
            'verification_inbox_enabled',
            'verification_inbox_provider',
            'verification_inbox_host',
            'verification_inbox_port',
            'verification_inbox_protocol',
            'verification_inbox_encryption',
            'verification_inbox_validate_certificate',
            'verification_inbox_username',
            'verification_inbox_password',
            'verification_inbox_folder_inbox',
            'verification_inbox_folder_spam',
            'verification_inbox_sync_frequency_minutes',
            'verification_inbox_sync_window_days',
            'verification_inbox_retention_mode',
            'verification_inbox_retention_days',
            'verification_inbox_keep_latest_count',
            'verification_inbox_spam_retention_days',
            'verification_inbox_preserve_flagged',
            'verification_inbox_auto_cleanup_enabled',
        ];
    }

    protected function selectedClinicId(): ?int
    {
        return AdminClinicScope::selectedClinicId();
    }

    protected function ensureClinicSelected(): bool
    {
        if ($this->selectedClinicId()) {
            return true;
        }

        Notification::make()
            ->title('Select a clinic first')
            ->body('Choose a clinic from the workspace switcher before updating inbox configuration.')
            ->warning()
            ->send();

        return false;
    }
}
