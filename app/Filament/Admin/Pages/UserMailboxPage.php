<?php

namespace App\Filament\Admin\Pages;

use App\Models\UserMailbox;
use App\Support\SaasEntitlements;
use App\Support\UserMailboxService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

class UserMailboxPage extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope-open';

    protected static string|UnitEnum|null $navigationGroup = 'Dashboard';

    protected static ?string $navigationLabel = 'Mailbox';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = '';

    protected static ?string $slug = 'mailbox';

    protected string $view = 'filament.admin.pages.user-mailbox';

    public string $folderFilter = UserMailboxService::FOLDER_INBOX;

    public string $readFilter = 'all';

    public string $search = '';

    public array $messages = [];

    public array $folderCounts = [];

    public ?string $selectedMessageUid = null;

    public ?string $selectedMessageFolder = null;

    public ?array $selectedMessage = null;

    public bool $composeModalOpen = false;

    public string $composeMode = 'compose';

    public array $composeFormData = [
        'to' => '',
        'cc' => '',
        'bcc' => '',
        'subject' => '',
        'body' => '',
    ];

    public array $composeAttachments = [];

    public static function canAccess(): bool
    {
        return (auth()->user()?->canAccessVerificationWorkspace() ?? false)
            && SaasEntitlements::userFeatureAllowed(auth()->user(), 'mailbox');
    }

    public function mount(UserMailboxService $service): void
    {
        $this->reloadMailbox($service);
    }

    public function updatedFolderFilter(): void
    {
        $this->reloadMailbox(app(UserMailboxService::class), preserveSelection: false);
    }

    public function updatedReadFilter(): void
    {
        $this->reloadMailbox(app(UserMailboxService::class), preserveSelection: false);
    }

    public function updatedSearch(): void
    {
        $this->reloadMailbox(app(UserMailboxService::class), preserveSelection: false);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getToolbarActions(): array
    {
        return [
            Action::make('refreshMailbox')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn (): bool => $this->isConfigured())
                ->action(function (UserMailboxService $service): void {
                    $this->reloadMailbox($service);

                    Notification::make()
                        ->title('Mailbox refreshed')
                        ->body('Live mailbox messages were reloaded from the server.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function openComposeModal(): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $this->composeMode = 'compose';
        $this->composeFormData = $this->composeFormDefaults();
        $this->composeModalOpen = true;
        $this->resetErrorBag();
    }

    public function openReplyModal(): void
    {
        if (! $this->isConfigured() || blank($this->selectedMessage['from_email'] ?? null)) {
            return;
        }

        $this->composeMode = 'reply';
        $this->composeFormData = $this->composeFormDefaults();
        $this->composeModalOpen = true;
        $this->resetErrorBag();
    }

    public function openReplyAllModal(): void
    {
        if (! $this->isConfigured() || blank($this->selectedMessage['from_email'] ?? null)) {
            return;
        }

        $this->composeMode = 'reply_all';
        $this->composeFormData = $this->composeFormDefaults(replyAll: true);
        $this->composeModalOpen = true;
        $this->resetErrorBag();
    }

    public function closeComposeModal(): void
    {
        $this->composeModalOpen = false;
        $this->composeAttachments = [];
        $this->resetErrorBag();
    }

    public function submitCompose(UserMailboxService $service): void
    {
        $validated = $this->validate([
            'composeFormData.to' => ['required', 'string'],
            'composeFormData.cc' => ['nullable', 'string'],
            'composeFormData.bcc' => ['nullable', 'string'],
            'composeFormData.subject' => ['required', 'string'],
            'composeFormData.body' => ['required', 'string'],
            'composeAttachments.*' => ['file', 'max:' . $this->attachmentLimitKilobytes()],
        ]);

        try {
            $payload = $validated['composeFormData'];
            $payload['attachments'] = $this->normalizedComposeAttachments();

            $this->sendCompose($payload, $service);
            $this->composeModalOpen = false;
            $this->composeFormData = $this->composeFormDefaults();
            $this->composeAttachments = [];
            $this->resetErrorBag();
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Email could not be sent')
                ->body($exception->getMessage() ?: 'Please verify the SMTP details in Mailbox Settings and try again.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function openMessage(string $folder, string $uid): void
    {
        $mailbox = $this->mailbox();

        if (! $mailbox) {
            return;
        }

        $this->selectedMessageFolder = $folder;
        $this->selectedMessageUid = $uid;
        $this->selectedMessage = app(UserMailboxService::class)->fetchMessage($mailbox, $folder, $uid);
    }

    public function getConnectionStatus(): array
    {
        $service = app(UserMailboxService::class);
        $mailbox = $this->mailbox();

        if (! $service->imapAvailable()) {
            return [
                'tone' => 'danger',
                'label' => 'IMAP extension missing',
                'description' => 'This server cannot open mailbox folders until the PHP IMAP extension is enabled.',
            ];
        }

        if (! $mailbox || ! $service->isConfigured($mailbox)) {
            return [
                'tone' => 'warning',
                'label' => 'Mailbox not configured',
                'description' => 'Open Mailbox Settings in the sidebar to connect your own inbox with live receive/send access.',
            ];
        }

        if (! $mailbox->enabled) {
            return [
                'tone' => 'warning',
                'label' => 'Mailbox disabled',
                'description' => 'Your mailbox settings are saved, but the mailbox is currently disabled.',
            ];
        }

        return [
            'tone' => 'success',
            'label' => 'Mailbox ready',
            'description' => 'Your mailbox is connected for live inbox access and direct sending.',
        ];
    }

    public function removeComposeAttachment(int $index): void
    {
        if (! array_key_exists($index, $this->composeAttachments)) {
            return;
        }

        unset($this->composeAttachments[$index]);
        $this->composeAttachments = array_values($this->composeAttachments);
    }

    public function mailbox(): ?UserMailbox
    {
        return app(UserMailboxService::class)->mailbox(auth()->user(), createIfMissing: false);
    }

    public function isConfigured(): bool
    {
        return app(UserMailboxService::class)->isConfigured($this->mailbox());
    }

    public function attachmentDownloadUrl(array $attachment): string
    {
        return route('admin.user-mailbox-attachments.download', [
            'folder' => $this->selectedMessageFolder,
            'uid' => $this->selectedMessageUid,
            'part' => $attachment['part_number'],
        ]);
    }

    public function messagePreviewUrl(): string
    {
        return route('admin.user-mailbox-messages.preview', [
            'folder' => $this->selectedMessageFolder,
            'uid' => $this->selectedMessageUid,
        ]);
    }

    protected function reloadMailbox(UserMailboxService $service, bool $preserveSelection = true): void
    {
        $mailbox = $this->mailbox();

        $this->messages = [];
        $this->folderCounts = [
            'all' => 0,
            'inbox' => 0,
            'spam' => 0,
            'sent' => 0,
            'unread' => 0,
        ];
        $this->selectedMessage = null;

        if (! $mailbox || ! $service->isConfigured($mailbox) || ! $mailbox->enabled || ! $service->imapAvailable()) {
            $this->selectedMessageUid = null;
            $this->selectedMessageFolder = null;

            return;
        }

        $this->folderCounts = $service->fetchFolderCounts($mailbox);
        $this->messages = $service->fetchMessages($mailbox, $this->folderFilter, $this->readFilter, $this->search, 50);

        $selectedUid = $preserveSelection ? $this->selectedMessageUid : null;
        $selectedFolder = $preserveSelection ? $this->selectedMessageFolder : null;

        $selectedSummary = collect($this->messages)->first(function (array $message) use ($selectedUid, $selectedFolder): bool {
            return $selectedUid !== null
                && $selectedFolder !== null
                && $message['uid'] === $selectedUid
                && $message['folder_key'] === $selectedFolder;
        });

        if (! $selectedSummary) {
            $selectedSummary = $this->messages[0] ?? null;
        }

        if (! $selectedSummary) {
            $this->selectedMessageUid = null;
            $this->selectedMessageFolder = null;

            return;
        }

        $this->selectedMessageUid = $selectedSummary['uid'];
        $this->selectedMessageFolder = $selectedSummary['folder_key'];
        $this->selectedMessage = $service->fetchMessage($mailbox, $this->selectedMessageFolder, $this->selectedMessageUid);
    }

    protected function composeFormDefaults(bool $replyAll = false): array
    {
        if (! $this->selectedMessage || ! $replyAll && $this->composeMode === 'compose') {
            return [
                'to' => '',
                'cc' => '',
                'bcc' => '',
                'subject' => '',
                'body' => '',
            ];
        }

        $replyRecipients = collect([
            $this->selectedMessage['from_email'] ?? null,
        ])->filter();

        $ccRecipients = collect($replyAll ? ($this->selectedMessage['cc'] ?? []) : [])
            ->merge($replyAll ? ($this->selectedMessage['to'] ?? []) : [])
            ->filter(fn (string $email): bool => ! $this->isOwnMailboxAddress($email))
            ->reject(fn (string $email): bool => $email === ($this->selectedMessage['from_email'] ?? null))
            ->unique()
            ->values();

        return [
            'to' => $replyRecipients->implode(', '),
            'cc' => $ccRecipients->implode(', '),
            'bcc' => '',
            'subject' => $this->replySubject((string) ($this->selectedMessage['subject'] ?? '')),
            'body' => '',
        ];
    }

    protected function sendCompose(array $data, UserMailboxService $service): void
    {
        $mailbox = $this->mailbox();

        if (! $service->smtpConfigured($mailbox)) {
            throw new \RuntimeException('Complete the outgoing mail settings in Mailbox Settings before sending email.');
        }

        $service->sendMessage($mailbox, $data);

        Notification::make()
            ->title('Email sent')
            ->body('Your message was sent from the connected mailbox.')
            ->success()
            ->persistent()
            ->send();
    }

    protected function replySubject(string $subject): string
    {
        return Str::startsWith(Str::lower($subject), 're:') ? $subject : 'Re: ' . $subject;
    }

    protected function isOwnMailboxAddress(string $email): bool
    {
        $mailbox = $this->mailbox();
        $email = Str::lower(trim($email));

        return in_array($email, array_filter([
            Str::lower((string) ($mailbox?->from_address ?? '')),
            Str::lower((string) ($mailbox?->smtp_username ?? '')),
            Str::lower((string) ($mailbox?->imap_username ?? '')),
        ]), true);
    }

    protected function attachmentLimitKilobytes(): int
    {
        return max(1, (int) (($this->mailbox()?->attachment_limit_mb ?? 25) * 1024));
    }

    protected function normalizedComposeAttachments(): array
    {
        return collect($this->composeAttachments)
            ->filter(fn ($file): bool => $file instanceof TemporaryUploadedFile)
            ->map(function (TemporaryUploadedFile $file): array {
                return [
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?: 'application/octet-stream',
                    'content' => file_get_contents($file->getRealPath()) ?: '',
                ];
            })
            ->filter(fn (array $attachment): bool => $attachment['content'] !== '')
            ->values()
            ->all();
    }

    public function composeModalHeading(): string
    {
        return match ($this->composeMode) {
            'reply' => 'Reply',
            'reply_all' => 'Reply All',
            default => 'Compose',
        };
    }

    public function isReplyMode(): bool
    {
        return in_array($this->composeMode, ['reply', 'reply_all'], true);
    }
}
