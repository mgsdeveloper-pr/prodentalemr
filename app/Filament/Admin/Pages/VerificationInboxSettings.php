<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\VerificationInboxAttachment;
use App\Models\VerificationInboxMailbox;
use App\Models\VerificationInboxMessage;
use App\Support\AdminClinicScope;
use App\Support\SaasEntitlements;
use App\Support\VerificationInboxService;
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
use Livewire\Attributes\Locked;
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

    #[Locked]
    public ?int $settingsClinicId = null;

    #[Locked]
    public array $cleanupMessageIds = [];

    #[Locked]
    public int $cleanupAttachmentCount = 0;

    public static function canAccess(): bool
    {
        return (auth()->user()?->canManageVerificationSettings() ?? false)
            && SaasEntitlements::userFeatureAllowed(auth()->user(), 'clinic_inbox', AdminClinicScope::selectedClinic());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->settingsClinicId = AdminClinicScope::selectedClinic()?->id;
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
                                ->default(true),
                            TextInput::make('verification_inbox_provider')
                                ->label('Provider label')
                                ->placeholder('Gmail, Outlook, Zoho, Custom IMAP'),
                            TextInput::make('verification_inbox_host')
                                ->label('IMAP host')
                                ->placeholder('imap.gmail.com'),
                            TextInput::make('verification_inbox_port')
                                ->label('Port')
                                ->numeric()
                                ->integer()->required()->minValue(1)->maxValue(65535)
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
                                ->integer()->required()->minValue(1)
                                ->default(15),
                            TextInput::make('verification_inbox_sync_window_days')
                                ->label('Sync window (days)')
                                ->numeric()
                                ->integer()->required()->minValue(1)
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
                                ->integer()->required()->minValue(1)
                                ->default(90),
                            TextInput::make('verification_inbox_keep_latest_count')
                                ->label('Keep latest emails')
                                ->numeric()
                                ->integer()->required()->minValue(1)
                                ->default(5000),
                            TextInput::make('verification_inbox_spam_retention_days')
                                ->label('Spam retention (days)')
                                ->numeric()
                                ->integer()->required()->minValue(1)
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
                ->visible(fn (): bool => $this->hasClinicScope())
                ->label('Test connection')
                ->action('testConnection')
                ->color('gray'),
            Action::make('syncNow')
                ->visible(fn (): bool => $this->hasClinicScope())
                ->label('Sync now')
                ->action('syncNow')
                ->color('gray'),
            Action::make('runCleanup')
                ->visible(fn (): bool => $this->hasClinicScope())
                ->label('Run cleanup')
                ->requiresConfirmation()
                ->mountUsing(fn () => $this->prepareCleanupPreview())
                ->modalHeading('Delete stored inbox messages?')
                ->modalDescription(fn (): string => $this->cleanupPreview())
                ->modalSubmitActionLabel('Delete previewed messages')
                ->action(fn () => $this->runCleanup(app(VerificationInboxService::class)))
                ->color('danger'),
            Action::make('save')
                ->visible(fn (): bool => $this->hasClinicScope())
                ->label('Save inbox settings')
                ->action('save'),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'Selected clinic: mailbox connection and retention. Sync and cleanup use saved settings only.';
    }

    public function getBreadcrumbs(): array
    {
        return [
            VerificationRequestResource::getUrl('index') => 'Verification',
            VerificationGeneralSettings::getUrl() => 'Settings',
            'Mailbox',
            'Clinic Inbox',
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

        $state = $this->form->getState();
        if (blank($state['verification_inbox_password'] ?? null)) {
            unset($state['verification_inbox_password']);
        }
        $draft = clone $this->getSettingsRecord();
        $draft->fill($state);
        $result = $service->testMailboxConnection($draft);

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

        $result = $service->sync(force: true, clinicId: $this->selectedClinicId());

        Notification::make()
            ->title($result['ok'] ? 'Inbox sync finished' : 'Inbox sync failed')
            ->body($result['message'])
            ->{$result['ok'] ? 'success' : 'danger'}()
            ->send();

        $this->settings = $this->getSettingsRecord()->fresh();
    }

    protected function runCleanup(VerificationInboxService $service): void
    {
        if (! $this->ensureClinicSelected()) {
            return;
        }

        $result = $service->cleanup($this->selectedClinicId(), $this->cleanupMessageIds);
        $this->cleanupMessageIds = [];

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
            'messages' => $clinicId ? VerificationInboxMessage::query()->where('clinic_id', $clinicId)->count() : 0,
            'attachments' => $clinicId
                ? VerificationInboxAttachment::query()
                    ->whereHas('message', fn ($query) => $query->where('clinic_id', $clinicId))
                    ->count()
                : 0,
            'last_sync' => $clinicId ? ($this->getSettingsRecord()->verification_inbox_last_synced_at?->format('d M Y, h:i A') ?? 'Not synced yet') : 'Select clinic first',
            'last_cleanup' => $clinicId ? ($this->getSettingsRecord()->verification_inbox_last_cleanup_at?->format('d M Y, h:i A') ?? 'Not cleaned yet') : 'Select clinic first',
        ];
    }

    protected function prepareCleanupPreview(): void
    {
        abort_unless(static::canAccess() && $this->hasClinicScope(), 403);
        $this->cleanupMessageIds = app(VerificationInboxService::class)->cleanupMessageIds($this->getSettingsRecord());
        $this->cleanupAttachmentCount = VerificationInboxAttachment::query()
            ->whereIn('verification_inbox_message_id', $this->cleanupMessageIds)->count();
    }

    protected function cleanupPreview(): string
    {
        return count($this->cleanupMessageIds).' stored messages and '.$this->cleanupAttachmentCount
            .' attachments qualify under the saved retention rules for '.$this->getSelectedClinicLabel()
            .'. Deletion is permanent. Unsaved settings are not applied. Messages on the mail server are unchanged.';
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
        return $this->hasClinicScope() ? $this->settingsClinicId : null;
    }

    public function hasClinicScope(): bool
    {
        return $this->settingsClinicId !== null
            && $this->settingsClinicId === AdminClinicScope::selectedClinic()?->id;
    }

    protected function ensureClinicSelected(): bool
    {
        abort_unless(static::canAccess(), 403);
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
