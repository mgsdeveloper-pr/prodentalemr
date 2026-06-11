<?php

namespace App\Filament\Admin\Pages;

use App\Models\VerificationInboxAttachment;
use App\Models\VerificationInboxMessage;
use App\Support\AdminClinicScope;
use App\Support\VerificationInboxService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class VerificationInbox extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Verifications';

    protected static ?string $navigationLabel = 'Clinic Inbox';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = '';

    protected static ?string $slug = 'inbox';

    protected string $view = 'filament.admin.pages.verification-inbox';

    public string $folderFilter = 'all';

    public string $readFilter = 'all';

    public string $search = '';

    public ?int $selectedMessageId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessVerificationWorkspace() ?? false;
    }

    public function mount(): void
    {
        $this->selectedMessageId = $this->getMessages()->first()?->getKey();
    }

    public function selectFolder(string $folder): void
    {
        $this->folderFilter = $folder;
        $this->selectedMessageId = $this->getMessages()->first()?->getKey();
    }

    public function openMessage(int $messageId): void
    {
        $message = $this->query()->findOrFail($messageId);

        $this->selectedMessageId = $message->getKey();

        if (! $message->is_read) {
            $message->forceFill(['is_read' => true])->save();
        }
    }

    public function refreshInbox(VerificationInboxService $service): void
    {
        $result = $service->sync(force: true, clinicId: AdminClinicScope::selectedClinicId());

        Notification::make()
            ->title($result['ok'] ? 'Inbox refreshed' : 'Inbox refresh failed')
            ->body($result['message'] ?? 'Refresh completed.')
            ->{$result['ok'] ? 'success' : 'danger'}()
            ->send();

        $this->selectedMessageId = $this->getMessages()->first()?->getKey();
    }

    public function getMessages(): Collection
    {
        return $this->query()
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->limit(80)
            ->get();
    }

    public function getSelectedMessage(): ?VerificationInboxMessage
    {
        $message = null;

        if ($this->selectedMessageId) {
            $message = $this->query()
                ->with('attachments')
                ->find($this->selectedMessageId);
        }

        if (! $message) {
            $message = $this->query()
                ->with('attachments')
                ->orderByDesc('received_at')
                ->orderByDesc('id')
                ->first();
        }

        if ($message && $this->selectedMessageId !== $message->getKey()) {
            $this->selectedMessageId = $message->getKey();
        }

        return $message;
    }

    public function getFolderCounts(): array
    {
        $base = $this->query();

        return [
            'all' => (clone $base)->count(),
            'inbox' => (clone $base)->where('folder_type', VerificationInboxService::FOLDER_INBOX)->count(),
            'spam' => (clone $base)->where('folder_type', VerificationInboxService::FOLDER_SPAM)->count(),
            'unread' => (clone $base)->where('is_read', false)->count(),
        ];
    }

    public function getSummary(): array
    {
        $messageIds = $this->query()->pluck('id');
        $attachmentBytes = (int) VerificationInboxAttachment::query()
            ->whereIn('verification_inbox_message_id', $messageIds)
            ->sum('file_size');
        $mailbox = app(VerificationInboxService::class)->mailbox(AdminClinicScope::selectedClinicId());

        return [
            'messages' => $messageIds->count(),
            'unread' => (clone $this->query())->where('is_read', false)->count(),
            'attachments' => VerificationInboxAttachment::query()->whereIn('verification_inbox_message_id', $messageIds)->count(),
            'storage' => $attachmentBytes > 0 ? number_format($attachmentBytes / 1048576, 2) . ' MB' : '0 MB',
            'last_sync' => $mailbox?->verification_inbox_last_synced_at?->format('d M Y, h:i A') ?? 'Not synced yet',
            'last_cleanup' => $mailbox?->verification_inbox_last_cleanup_at?->format('d M Y, h:i A') ?? 'Not cleaned yet',
        ];
    }

    public function getConnectionStatus(): array
    {
        $service = app(VerificationInboxService::class);
        $selectedClinicId = AdminClinicScope::selectedClinicId();
        $mailbox = $service->mailbox($selectedClinicId);

        if (! $service->imapAvailable()) {
            return [
                'tone' => 'danger',
                'label' => 'IMAP extension missing',
                'description' => 'This server cannot connect to the mailbox until the PHP IMAP extension is installed.',
            ];
        }

        if (! $selectedClinicId) {
            return [
                'tone' => 'warning',
                'label' => 'All assigned clinics',
                'description' => 'Select a clinic in the workspace to manage a specific mailbox, or refresh to sync all accessible clinic mailboxes.',
            ];
        }

        if (! $mailbox) {
            return [
                'tone' => 'warning',
                'label' => 'Clinic mailbox not configured',
                'description' => 'Open Inbox Configuration and add mailbox details for this clinic.',
            ];
        }

        if (! $service->isConfigured($selectedClinicId)) {
            return [
                'tone' => 'warning',
                'label' => 'Mailbox not configured',
                'description' => 'Open Inbox Configuration and add this clinic mailbox connection details.',
            ];
        }

        if (! $mailbox->verification_inbox_enabled) {
            return [
                'tone' => 'warning',
                'label' => 'Sync disabled',
                'description' => 'This clinic mailbox is saved, but sync is currently disabled.',
            ];
        }

        return [
            'tone' => 'success',
            'label' => 'Mailbox ready',
            'description' => 'Inbox and Spam for this clinic can be refreshed into the synced workspace from this screen.',
        ];
    }

    public function attachmentDownloadUrl(VerificationInboxAttachment $attachment): string
    {
        return route('admin.verification-inbox-attachments.download', $attachment);
    }

    public function messagePreviewUrl(VerificationInboxMessage $message): string
    {
        return route('admin.verification-inbox-messages.preview', $message);
    }

    protected function query(): Builder
    {
        $query = AdminClinicScope::apply(VerificationInboxMessage::query(), 'clinic_id')
            ->when($this->folderFilter === VerificationInboxService::FOLDER_INBOX, fn (Builder $query) => $query->where('folder_type', VerificationInboxService::FOLDER_INBOX))
            ->when($this->folderFilter === VerificationInboxService::FOLDER_SPAM, fn (Builder $query) => $query->where('folder_type', VerificationInboxService::FOLDER_SPAM))
            ->when($this->readFilter === 'unread', fn (Builder $query) => $query->where('is_read', false))
            ->when($this->readFilter === 'read', fn (Builder $query) => $query->where('is_read', true))
            ->when(filled($this->search), function (Builder $query): void {
                $query->where(function (Builder $builder): void {
                    $builder
                        ->where('subject', 'like', '%' . $this->search . '%')
                        ->orWhere('from_name', 'like', '%' . $this->search . '%')
                        ->orWhere('from_email', 'like', '%' . $this->search . '%')
                        ->orWhere('snippet', 'like', '%' . $this->search . '%');
                });
            });

        return $query;
    }
}
