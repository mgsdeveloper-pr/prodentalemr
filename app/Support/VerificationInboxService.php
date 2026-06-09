<?php

namespace App\Support;

use App\Models\SaasSetting;
use App\Models\VerificationInboxAttachment;
use App\Models\VerificationInboxMessage;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class VerificationInboxService
{
    public const FOLDER_INBOX = 'inbox';
    public const FOLDER_SPAM = 'spam';

    public function settings(): SaasSetting
    {
        return SaasSetting::current();
    }

    public function imapAvailable(): bool
    {
        return function_exists('imap_open');
    }

    public function isConfigured(): bool
    {
        $settings = $this->settings();

        return filled($settings->verification_inbox_host)
            && filled($settings->verification_inbox_username)
            && filled($settings->verification_inbox_password);
    }

    public function testConnection(): array
    {
        if (! $this->imapAvailable()) {
            return [
                'ok' => false,
                'message' => 'PHP IMAP extension is not installed on this server.',
            ];
        }

        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Inbox host, username, and password are required before testing the connection.',
            ];
        }

        $settings = $this->settings();
        $connection = $this->openMailbox((string) $settings->verification_inbox_folder_inbox);

        if (! $connection) {
            return [
                'ok' => false,
                'message' => $this->lastImapError() ?: 'Unable to connect to the configured mailbox.',
            ];
        }

        $mailboxes = @imap_list($connection, $this->mailboxRoot(), '*') ?: [];
        @imap_close($connection);

        return [
            'ok' => true,
            'message' => 'Mailbox connection verified successfully.',
            'mailboxes' => array_values(array_map(
                fn ($mailbox): string => Str::after((string) $mailbox, $this->mailboxRoot()),
                $mailboxes
            )),
        ];
    }

    public function sync(bool $force = false): array
    {
        $settings = $this->settings();

        if (! $settings->verification_inbox_enabled) {
            return [
                'ok' => false,
                'skipped' => true,
                'message' => 'Inbox sync is disabled in Inbox Configuration.',
            ];
        }

        if (! $this->imapAvailable()) {
            return [
                'ok' => false,
                'message' => 'PHP IMAP extension is not installed on this server.',
            ];
        }

        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Inbox connection details are incomplete.',
            ];
        }

        if (! $force && ! $this->shouldSyncNow()) {
            return [
                'ok' => true,
                'skipped' => true,
                'message' => 'Inbox sync is not due yet.',
            ];
        }

        $stats = [
            'ok' => true,
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'attachments' => 0,
            'message' => 'Inbox sync completed successfully.',
        ];

        foreach ($this->folderMap() as $folderType => $folderName) {
            $connection = $this->openMailbox($folderName);

            if (! $connection) {
                return [
                    'ok' => false,
                    'message' => $this->lastImapError() ?: "Unable to open the {$folderType} mailbox.",
                ];
            }

            $searchCriteria = $this->buildSearchCriteria();
            $uids = @imap_search($connection, $searchCriteria, SE_UID) ?: [];
            rsort($uids);

            foreach ($uids as $uid) {
                $result = $this->syncMessage($connection, (string) $folderName, (string) $folderType, (string) $uid);

                $stats['synced']++;
                $stats['attachments'] += $result['attachments'];
                $stats[$result['status']]++;
            }

            @imap_close($connection);
        }

        $settings->forceFill([
            'verification_inbox_last_synced_at' => now(),
        ])->save();

        return $stats;
    }

    public function cleanup(): array
    {
        $settings = $this->settings();

        if (! $settings->verification_inbox_auto_cleanup_enabled) {
            return [
                'ok' => false,
                'skipped' => true,
                'message' => 'Inbox cleanup is disabled in Inbox Configuration.',
            ];
        }

        $deletedMessages = 0;
        $deletedAttachments = 0;

        $inboxQuery = VerificationInboxMessage::query()
            ->where('folder_type', self::FOLDER_INBOX)
            ->where('is_protected', false);

        $spamQuery = VerificationInboxMessage::query()
            ->where('folder_type', self::FOLDER_SPAM)
            ->where('is_protected', false);

        if ($settings->verification_inbox_preserve_flagged) {
            $inboxQuery->where('is_flagged', false);
            $spamQuery->where('is_flagged', false);
        }

        if ($settings->verification_inbox_retention_mode === 'days') {
            $cutoff = now()->subDays((int) $settings->verification_inbox_retention_days);
            $deletedMessages += $this->deleteMessages((clone $inboxQuery)->where('received_at', '<', $cutoff), $deletedAttachments);
        } elseif ($settings->verification_inbox_retention_mode === 'count') {
            $keep = max(0, (int) $settings->verification_inbox_keep_latest_count);
            $idsToDelete = (clone $inboxQuery)
                ->orderByDesc('received_at')
                ->skip($keep)
                ->pluck('id');

            $deletedMessages += $this->deleteMessages(
                VerificationInboxMessage::query()->whereIn('id', $idsToDelete),
                $deletedAttachments
            );
        }

        $spamCutoff = now()->subDays((int) $settings->verification_inbox_spam_retention_days);
        $deletedMessages += $this->deleteMessages((clone $spamQuery)->where('received_at', '<', $spamCutoff), $deletedAttachments);

        $settings->forceFill([
            'verification_inbox_last_cleanup_at' => now(),
        ])->save();

        return [
            'ok' => true,
            'deleted_messages' => $deletedMessages,
            'deleted_attachments' => $deletedAttachments,
            'message' => 'Inbox cleanup completed successfully.',
        ];
    }

    public function shouldSyncNow(): bool
    {
        $settings = $this->settings();
        $lastSyncedAt = $settings->verification_inbox_last_synced_at;

        if (! $lastSyncedAt) {
            return true;
        }

        return Carbon::parse($lastSyncedAt)
            ->addMinutes((int) $settings->verification_inbox_sync_frequency_minutes)
            ->isPast();
    }

    protected function syncMessage($connection, string $folderName, string $folderType, string $uid): array
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
            'mailbox_uid' => $uid,
            'folder_name' => $folderName,
            'folder_type' => $folderType,
            'external_message_id' => filled($overview->message_id ?? null) ? trim((string) $overview->message_id, '<>') : null,
            'message_hash' => sha1(implode('|', [
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
        $path = 'verification-inbox/' . $message->id . '/' . $safeName . '-' . Str::random(8) . $extension;

        Storage::disk('verification_inbox')->put($path, $attachment['content']);

        return $path;
    }

    protected function deleteMessages($query, int &$deletedAttachments): int
    {
        $messages = $query->get();

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
            'email' => ($mailbox && $host && $host !== '.SYNTAX-ERROR.')
                ? $mailbox . '@' . $host
                : null,
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

        return trim(collect($segments)
            ->map(fn ($segment) => $segment->text ?? '')
            ->implode(''));
    }

    protected function folderMap(): array
    {
        $settings = $this->settings();

        return array_filter([
            self::FOLDER_INBOX => $settings->verification_inbox_folder_inbox ?: 'INBOX',
            self::FOLDER_SPAM => $settings->verification_inbox_folder_spam ?: null,
        ]);
    }

    protected function buildSearchCriteria(): string
    {
        $days = max(1, (int) $this->settings()->verification_inbox_sync_window_days);

        return 'SINCE "' . now()->subDays($days)->format('d-M-Y') . '"';
    }

    protected function openMailbox(string $folderName)
    {
        return @imap_open(
            $this->mailboxPath($folderName),
            (string) $this->settings()->verification_inbox_username,
            (string) $this->settings()->verification_inbox_password
        );
    }

    protected function mailboxRoot(): string
    {
        return $this->mailboxPath('');
    }

    protected function mailboxPath(string $folderName): string
    {
        $settings = $this->settings();
        $flags = ['/' . ($settings->verification_inbox_protocol ?: 'imap')];

        $encryption = strtolower((string) $settings->verification_inbox_encryption);

        if ($encryption === 'ssl') {
            $flags[] = '/ssl';
        } elseif ($encryption === 'tls') {
            $flags[] = '/tls';
        }

        if (! $settings->verification_inbox_validate_certificate) {
            $flags[] = '/novalidate-cert';
        }

        $port = $settings->verification_inbox_port ?: 993;

        return '{' . $settings->verification_inbox_host . ':' . $port . implode('', $flags) . '}' . $folderName;
    }

    protected function lastImapError(): ?string
    {
        if (! function_exists('imap_last_error')) {
            return null;
        }

        return @imap_last_error() ?: null;
    }
}
