<?php

namespace App\Support;

use App\Models\VerificationInboxAttachment;
use App\Models\VerificationInboxMailbox;
use App\Models\VerificationInboxMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VerificationInboxService
{
    public const FOLDER_INBOX = 'inbox';
    public const FOLDER_SPAM = 'spam';

    public function mailbox(?int $clinicId = null, bool $createIfMissing = false): ?VerificationInboxMailbox
    {
        $clinicId ??= AdminClinicScope::selectedClinicId();

        if (! filled($clinicId)) {
            return null;
        }

        $mailbox = VerificationInboxMailbox::query()
            ->where('clinic_id', (int) $clinicId)
            ->first();

        if ($mailbox || ! $createIfMissing) {
            return $mailbox;
        }

        return VerificationInboxMailbox::query()->create(
            VerificationInboxMailbox::defaultState((int) $clinicId)
        );
    }

    public function imapAvailable(): bool
    {
        return function_exists('imap_open');
    }

    public function isConfigured(?int $clinicId = null): bool
    {
        $mailbox = $this->mailbox($clinicId);

        return $mailbox instanceof VerificationInboxMailbox
            && filled($mailbox->verification_inbox_host)
            && filled($mailbox->verification_inbox_username)
            && filled($mailbox->verification_inbox_password);
    }

    public function testConnection(?int $clinicId = null): array
    {
        if (! $this->imapAvailable()) {
            return [
                'ok' => false,
                'message' => 'PHP IMAP extension is not installed on this server.',
            ];
        }

        $mailbox = $this->mailbox($clinicId);

        if (! $mailbox) {
            return [
                'ok' => false,
                'message' => 'Select a clinic from the workspace first.',
            ];
        }

        if (! $this->isConfigured((int) $mailbox->clinic_id)) {
            return [
                'ok' => false,
                'message' => 'Inbox host, username, and password are required before testing the connection.',
            ];
        }

        $connection = $this->openMailbox($mailbox, (string) $mailbox->verification_inbox_folder_inbox);

        if (! $connection) {
            return [
                'ok' => false,
                'message' => $this->lastImapError() ?: 'Unable to connect to the configured mailbox.',
            ];
        }

        $mailboxes = @imap_list($connection, $this->mailboxRoot($mailbox), '*') ?: [];
        @imap_close($connection);

        return [
            'ok' => true,
            'message' => 'Mailbox connection verified successfully.',
            'mailboxes' => array_values(array_map(
                fn ($value): string => Str::after((string) $value, $this->mailboxRoot($mailbox)),
                $mailboxes
            )),
        ];
    }

    public function sync(bool $force = false, ?int $clinicId = null): array
    {
        if (! $this->imapAvailable()) {
            return [
                'ok' => false,
                'message' => 'PHP IMAP extension is not installed on this server.',
            ];
        }

        $mailboxes = $this->targetMailboxes($clinicId);

        if ($mailboxes === []) {
            return [
                'ok' => false,
                'message' => filled($clinicId) || AdminClinicScope::selectedClinicId()
                    ? 'This clinic does not have an inbox mailbox configured yet.'
                    : 'No clinic-specific inbox mailboxes are available for the current scope.',
            ];
        }

        $stats = [
            'ok' => true,
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'attachments' => 0,
            'mailboxes' => 0,
            'message' => 'Inbox sync completed successfully.',
        ];

        foreach ($mailboxes as $mailbox) {
            $result = $this->syncMailbox($mailbox, $force);

            if (! ($result['ok'] ?? false)) {
                return $result;
            }

            $stats['mailboxes']++;
            $stats['synced'] += (int) ($result['synced'] ?? 0);
            $stats['created'] += (int) ($result['created'] ?? 0);
            $stats['updated'] += (int) ($result['updated'] ?? 0);
            $stats['attachments'] += (int) ($result['attachments'] ?? 0);
        }

        return $stats;
    }

    public function cleanup(?int $clinicId = null): array
    {
        $mailboxes = $this->targetMailboxes($clinicId, onlyEnabled: false)
            ->filter(fn (VerificationInboxMailbox $mailbox): bool => (bool) $mailbox->verification_inbox_auto_cleanup_enabled)
            ->values();

        if ($mailboxes->isEmpty()) {
            return [
                'ok' => false,
                'skipped' => true,
                'message' => 'No clinic mailbox currently has scheduled cleanup enabled.',
            ];
        }

        $deletedMessages = 0;
        $deletedAttachments = 0;

        foreach ($mailboxes as $mailbox) {
            $inboxQuery = VerificationInboxMessage::query()
                ->where('clinic_id', $mailbox->clinic_id)
                ->where('folder_type', self::FOLDER_INBOX)
                ->where('is_protected', false);

            $spamQuery = VerificationInboxMessage::query()
                ->where('clinic_id', $mailbox->clinic_id)
                ->where('folder_type', self::FOLDER_SPAM)
                ->where('is_protected', false);

            if ($mailbox->verification_inbox_preserve_flagged) {
                $inboxQuery->where('is_flagged', false);
                $spamQuery->where('is_flagged', false);
            }

            if ($mailbox->verification_inbox_retention_mode === 'days') {
                $cutoff = now()->subDays((int) $mailbox->verification_inbox_retention_days);
                $deletedMessages += $this->deleteMessages((clone $inboxQuery)->where('received_at', '<', $cutoff), $deletedAttachments);
            } elseif ($mailbox->verification_inbox_retention_mode === 'count') {
                $keep = max(0, (int) $mailbox->verification_inbox_keep_latest_count);
                $idsToDelete = (clone $inboxQuery)
                    ->orderByDesc('received_at')
                    ->skip($keep)
                    ->pluck('id');

                $deletedMessages += $this->deleteMessages(
                    VerificationInboxMessage::query()->whereIn('id', $idsToDelete),
                    $deletedAttachments
                );
            }

            $spamCutoff = now()->subDays((int) $mailbox->verification_inbox_spam_retention_days);
            $deletedMessages += $this->deleteMessages((clone $spamQuery)->where('received_at', '<', $spamCutoff), $deletedAttachments);

            $mailbox->forceFill([
                'verification_inbox_last_cleanup_at' => now(),
            ])->save();
        }

        return [
            'ok' => true,
            'deleted_messages' => $deletedMessages,
            'deleted_attachments' => $deletedAttachments,
            'message' => 'Inbox cleanup completed successfully.',
        ];
    }

    public function shouldSyncNow(VerificationInboxMailbox $mailbox): bool
    {
        $lastSyncedAt = $mailbox->verification_inbox_last_synced_at;

        if (! $lastSyncedAt) {
            return true;
        }

        return Carbon::parse($lastSyncedAt)
            ->addMinutes((int) $mailbox->verification_inbox_sync_frequency_minutes)
            ->isPast();
    }

    protected function targetMailboxes(?int $clinicId = null, bool $onlyEnabled = true)
    {
        $query = VerificationInboxMailbox::query();

        if ($onlyEnabled) {
            $query->where('verification_inbox_enabled', true);
        }

        if (filled($clinicId)) {
            return $query->where('clinic_id', (int) $clinicId)->get();
        }

        if (auth()->check()) {
            $selectedClinicId = AdminClinicScope::selectedClinicId();

            if (filled($selectedClinicId)) {
                return $query->where('clinic_id', (int) $selectedClinicId)->get();
            }

            $user = auth()->user();

            if (! $user?->hasFullVerificationClinicAccess()) {
                $accessibleClinicIds = $user?->verificationAccessibleClinicIds() ?? [];

                if ($accessibleClinicIds === []) {
                    return collect();
                }

                $query->whereIn('clinic_id', $accessibleClinicIds);
            }

            return $query->get();
        }

        return $query->get();
    }

    protected function syncMailbox(VerificationInboxMailbox $mailbox, bool $force = false): array
    {
        if (! $mailbox->verification_inbox_enabled) {
            return [
                'ok' => true,
                'skipped' => true,
                'message' => 'Inbox sync is disabled for this clinic mailbox.',
                'synced' => 0,
                'created' => 0,
                'updated' => 0,
                'attachments' => 0,
            ];
        }

        if (! $this->isConfigured((int) $mailbox->clinic_id)) {
            return [
                'ok' => false,
                'message' => 'Inbox connection details are incomplete for clinic ID ' . $mailbox->clinic_id . '.',
            ];
        }

        if (! $force && ! $this->shouldSyncNow($mailbox)) {
            return [
                'ok' => true,
                'skipped' => true,
                'message' => 'Inbox sync is not due yet.',
                'synced' => 0,
                'created' => 0,
                'updated' => 0,
                'attachments' => 0,
            ];
        }

        $stats = [
            'ok' => true,
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'attachments' => 0,
        ];

        foreach ($this->folderMap($mailbox) as $folderType => $folderName) {
            $connection = $this->openMailbox($mailbox, $folderName);

            if (! $connection) {
                return [
                    'ok' => false,
                    'message' => $this->lastImapError() ?: "Unable to open the {$folderType} mailbox for clinic ID {$mailbox->clinic_id}.",
                ];
            }

            $uids = @imap_search($connection, $this->buildSearchCriteria($mailbox), SE_UID) ?: [];
            rsort($uids);

            foreach ($uids as $uid) {
                $result = $this->syncMessage($mailbox, $connection, (string) $folderName, (string) $folderType, (string) $uid);
                $stats['synced']++;
                $stats['attachments'] += $result['attachments'];
                $stats[$result['status']]++;
            }

            @imap_close($connection);
        }

        $mailbox->forceFill([
            'verification_inbox_last_synced_at' => now(),
        ])->save();

        return $stats + ['message' => 'Inbox sync completed successfully.'];
    }

    protected function syncMessage(VerificationInboxMailbox $mailbox, $connection, string $folderName, string $folderType, string $uid): array
    {
        $overviewItems = @imap_fetch_overview($connection, $uid, FT_UID) ?: [];
        $overview = $overviewItems[0] ?? null;

        if (! $overview) {
            return [
                'status' => 'updated',
                'attachments' => 0,
            ];
        }

        $subject = $this->decodeMimeHeader((string) ($overview->subject ?? ''));
        $from = $this->parseAddress((string) ($overview->from ?? ''));
        $replyTo = $this->parseAddress((string) ($overview->reply_to ?? ''));
        $receivedAt = filled($overview->date ?? null) ? Carbon::parse((string) $overview->date) : now();
        $headers = [
            'udate' => $overview->udate ?? null,
            'message_id' => $overview->message_id ?? null,
            'references' => $overview->references ?? null,
        ];

        $structure = @imap_fetchstructure($connection, $uid, FT_UID);
        $parsed = $this->extractBodyAndAttachments($connection, $uid, $structure);

        $payload = [
            'clinic_id' => $mailbox->clinic_id,
            'mailbox_uid' => $uid,
            'folder_name' => $folderName,
            'folder_type' => $folderType,
            'external_message_id' => filled($overview->message_id ?? null) ? trim((string) $overview->message_id, '<>') : null,
            'message_hash' => sha1(implode('|', [
                $mailbox->clinic_id,
                $folderName,
                $uid,
                $subject,
                $receivedAt?->timestamp,
            ])),
            'subject' => $subject ?: '(No subject)',
            'from_name' => $from['name'],
            'from_email' => $from['email'],
            'reply_to_email' => $replyTo['email'],
            'to_emails' => $this->parseAddressList((string) ($overview->to ?? '')),
            'cc_emails' => $this->parseAddressList((string) ($overview->cc ?? '')),
            'bcc_emails' => $this->parseAddressList((string) ($overview->bcc ?? '')),
            'snippet' => Str::limit(trim(preg_replace('/\s+/', ' ', $parsed['text'] ?: strip_tags($parsed['html']))), 240),
            'body_text' => $parsed['text'],
            'body_html' => $parsed['html'],
            'headers' => $headers,
            'received_at' => $receivedAt,
            'synced_at' => now(),
            'is_read' => (bool) ($overview->seen ?? false),
            'is_flagged' => (bool) ($overview->flagged ?? false),
            'is_spam' => $folderType === self::FOLDER_SPAM,
            'has_attachments' => ! empty($parsed['attachments']),
            'attachment_count' => count($parsed['attachments']),
            'size_bytes' => isset($overview->size) ? (int) $overview->size : null,
        ];

        $message = VerificationInboxMessage::query()->firstOrNew([
            'clinic_id' => $mailbox->clinic_id,
            'folder_name' => $folderName,
            'mailbox_uid' => $uid,
        ]);

        $status = $message->exists ? 'updated' : 'created';

        DB::transaction(function () use ($message, $payload, $parsed): void {
            $message->fill($payload)->save();

            $message->attachments()->get()->each(function (VerificationInboxAttachment $attachment): void {
                if (filled($attachment->storage_path)) {
                    Storage::disk($attachment->storage_disk ?: 'verification_inbox')->delete($attachment->storage_path);
                }
            });

            $message->attachments()->delete();

            foreach ($parsed['attachments'] as $attachment) {
                $storedPath = $this->storeAttachment($message, $attachment);

                $message->attachments()->create([
                    'file_name' => $attachment['file_name'],
                    'mime_type' => $attachment['mime_type'],
                    'file_size' => $attachment['file_size'],
                    'part_number' => $attachment['part_number'],
                    'content_id' => $attachment['content_id'],
                    'is_inline' => $attachment['is_inline'],
                    'storage_disk' => 'verification_inbox',
                    'storage_path' => $storedPath,
                ]);
            }
        });

        return [
            'status' => $status,
            'attachments' => count($parsed['attachments']),
        ];
    }

    protected function extractBodyAndAttachments($connection, string $uid, $structure): array
    {
        $result = [
            'text' => '',
            'html' => '',
            'attachments' => [],
        ];

        if (! $structure) {
            $body = @imap_body($connection, $uid, FT_UID) ?: '';
            $result['text'] = $body;

            return $result;
        }

        $this->walkStructure($connection, $uid, $structure, '', $result);

        return $result;
    }

    protected function walkStructure($connection, string $uid, object $structure, string $partNumber, array &$result): void
    {
        $currentPartNumber = $partNumber !== '' ? $partNumber : '1';
        $isMultipart = isset($structure->parts) && is_array($structure->parts) && count($structure->parts) > 0;

        if ($isMultipart) {
            foreach ($structure->parts as $index => $part) {
                $childPartNumber = $partNumber === '' ? (string) ($index + 1) : $partNumber . '.' . ($index + 1);
                $this->walkStructure($connection, $uid, $part, $childPartNumber, $result);
            }

            return;
        }

        $parameters = $this->collectParameters($structure);
        $content = $this->fetchDecodedPart($connection, $uid, $currentPartNumber, (int) ($structure->encoding ?? 0));
        $isAttachment = isset($parameters['filename']) || isset($parameters['name']) || strtolower((string) ($structure->disposition ?? '')) === 'attachment';

        if ($isAttachment) {
            $result['attachments'][] = [
                'file_name' => $parameters['filename'] ?? $parameters['name'] ?? ('attachment-' . $currentPartNumber),
                'mime_type' => $this->resolveMimeType($structure),
                'file_size' => isset($structure->bytes) ? (int) $structure->bytes : strlen($content),
                'part_number' => $currentPartNumber,
                'content_id' => $structure->id ?? null,
                'is_inline' => strtolower((string) ($structure->disposition ?? '')) === 'inline',
                'content' => $content,
            ];

            return;
        }

        $subtype = strtoupper((string) ($structure->subtype ?? ''));

        if ((int) ($structure->type ?? 0) === TYPETEXT && $subtype === 'PLAIN') {
            $result['text'] .= trim($content) . "\n\n";

            return;
        }

        if ((int) ($structure->type ?? 0) === TYPETEXT && $subtype === 'HTML') {
            $result['html'] .= $content;

            return;
        }

        if ($result['text'] === '' && filled(trim($content))) {
            $result['text'] .= trim(strip_tags($content)) . "\n\n";
        }
    }

    protected function collectParameters(object $structure): array
    {
        $items = [];

        foreach (['parameters', 'dparameters'] as $property) {
            foreach ($structure->{$property} ?? [] as $parameter) {
                $attribute = strtolower((string) ($parameter->attribute ?? ''));
                $value = $this->decodeMimeHeader((string) ($parameter->value ?? ''));

                if ($attribute !== '') {
                    $items[$attribute] = $value;
                }
            }
        }

        return $items;
    }

    protected function fetchDecodedPart($connection, string $uid, string $partNumber, int $encoding): string
    {
        $body = @imap_fetchbody($connection, $uid, $partNumber, FT_UID) ?: '';

        return match ($encoding) {
            3 => base64_decode($body, true) ?: '',
            4 => quoted_printable_decode($body),
            default => $body,
        };
    }

    protected function resolveMimeType(object $structure): ?string
    {
        $primary = match ((int) ($structure->type ?? 0)) {
            0 => 'text',
            1 => 'multipart',
            2 => 'message',
            3 => 'application',
            4 => 'audio',
            5 => 'image',
            6 => 'video',
            7 => 'other',
            default => null,
        };

        if (! $primary) {
            return null;
        }

        $subtype = strtolower((string) ($structure->subtype ?? 'octet-stream'));

        return $primary . '/' . $subtype;
    }

    protected function storeAttachment(VerificationInboxMessage $message, array $attachment): ?string
    {
        if (($attachment['content'] ?? '') === '') {
            return null;
        }

        $safeName = Str::slug(pathinfo((string) $attachment['file_name'], PATHINFO_FILENAME));
        $extension = pathinfo((string) $attachment['file_name'], PATHINFO_EXTENSION);
        $extension = $extension !== '' ? '.' . $extension : '';
        $path = 'verification-inbox/' . ($message->clinic_id ?: 'shared') . '/' . $message->id . '/' . $safeName . '-' . Str::random(8) . $extension;

        Storage::disk('verification_inbox')->put($path, $attachment['content']);

        return $path;
    }

    protected function deleteMessages($query, int &$deletedAttachments): int
    {
        $messages = $query->with('attachments')->get();

        foreach ($messages as $message) {
            foreach ($message->attachments as $attachment) {
                if (filled($attachment->storage_path)) {
                    Storage::disk($attachment->storage_disk ?: 'verification_inbox')->delete($attachment->storage_path);
                    $deletedAttachments++;
                }
            }

            $message->delete();
        }

        return $messages->count();
    }

    protected function parseAddress(string $raw): array
    {
        if (! function_exists('imap_rfc822_parse_adrlist')) {
            return ['name' => null, 'email' => null];
        }

        $addresses = @imap_rfc822_parse_adrlist($raw, '') ?: [];
        $address = $addresses[0] ?? null;

        if (! $address) {
            return ['name' => null, 'email' => null];
        }

        $mailbox = $address->mailbox ?? null;
        $host = $address->host ?? null;

        return [
            'name' => filled($address->personal ?? null) ? $this->decodeMimeHeader((string) $address->personal) : null,
            'email' => ($mailbox && $host && $host !== '.SYNTAX-ERROR.') ? $mailbox . '@' . $host : null,
        ];
    }

    protected function parseAddressList(string $raw): array
    {
        if (! function_exists('imap_rfc822_parse_adrlist')) {
            return [];
        }

        $addresses = @imap_rfc822_parse_adrlist($raw, '') ?: [];

        return collect($addresses)
            ->map(function ($address): ?string {
                $mailbox = $address->mailbox ?? null;
                $host = $address->host ?? null;

                if (! $mailbox || ! $host || $host === '.SYNTAX-ERROR.') {
                    return null;
                }

                return $mailbox . '@' . $host;
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function decodeMimeHeader(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (! function_exists('imap_mime_header_decode')) {
            return trim($value);
        }

        $segments = @imap_mime_header_decode($value) ?: [];

        return trim(collect($segments)->map(fn ($segment) => $segment->text ?? '')->implode(''));
    }

    protected function folderMap(VerificationInboxMailbox $mailbox): array
    {
        return array_filter([
            self::FOLDER_INBOX => $mailbox->verification_inbox_folder_inbox ?: 'INBOX',
            self::FOLDER_SPAM => $mailbox->verification_inbox_folder_spam ?: null,
        ]);
    }

    protected function buildSearchCriteria(VerificationInboxMailbox $mailbox): string
    {
        $days = max(1, (int) $mailbox->verification_inbox_sync_window_days);

        return 'SINCE "' . now()->subDays($days)->format('d-M-Y') . '"';
    }

    protected function openMailbox(VerificationInboxMailbox $mailbox, string $folderName)
    {
        return @imap_open(
            $this->mailboxPath($mailbox, $folderName),
            (string) $mailbox->verification_inbox_username,
            (string) $mailbox->verification_inbox_password
        );
    }

    protected function mailboxRoot(VerificationInboxMailbox $mailbox): string
    {
        return $this->mailboxPath($mailbox, '');
    }

    protected function mailboxPath(VerificationInboxMailbox $mailbox, string $folderName): string
    {
        $flags = ['/' . ($mailbox->verification_inbox_protocol ?: 'imap')];
        $encryption = strtolower((string) $mailbox->verification_inbox_encryption);

        if ($encryption === 'ssl') {
            $flags[] = '/ssl';
        } elseif ($encryption === 'tls') {
            $flags[] = '/tls';
        }

        if (! $mailbox->verification_inbox_validate_certificate) {
            $flags[] = '/novalidate-cert';
        }

        $port = $mailbox->verification_inbox_port ?: 993;

        return '{' . $mailbox->verification_inbox_host . ':' . $port . implode('', $flags) . '}' . $folderName;
    }

    protected function lastImapError(): ?string
    {
        if (! function_exists('imap_last_error')) {
            return null;
        }

        return @imap_last_error() ?: null;
    }
}
