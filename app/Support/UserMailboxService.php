<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserMailbox;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class UserMailboxService
{
    public const FOLDER_ALL = 'all';

    public const FOLDER_INBOX = 'inbox';

    public const FOLDER_SPAM = 'spam';

    public const FOLDER_SENT = 'sent';

    public function mailbox(?User $user = null, bool $createIfMissing = false): ?UserMailbox
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        $mailbox = $user->mailbox()->first();

        if ($mailbox || ! $createIfMissing) {
            return $mailbox;
        }

        return $user->mailbox()->create(
            UserMailbox::defaultState(
                $user->getKey(),
                $user->name,
                $user->email,
            ),
        );
    }

    public function imapAvailable(): bool
    {
        return function_exists('imap_open');
    }

    public function isConfigured(?UserMailbox $mailbox): bool
    {
        return $mailbox instanceof UserMailbox
            && filled($mailbox->imap_host)
            && filled($mailbox->imap_username)
            && filled($mailbox->imap_password);
    }

    public function smtpConfigured(?UserMailbox $mailbox): bool
    {
        return $mailbox instanceof UserMailbox
            && filled($mailbox->smtp_host ?: $mailbox->imap_host)
            && filled($mailbox->smtp_port)
            && filled($mailbox->smtp_username ?: $mailbox->imap_username)
            && filled($mailbox->smtp_password ?: $mailbox->imap_password)
            && filled($mailbox->from_address ?: $mailbox->smtp_username ?: $mailbox->imap_username);
    }

    public function testConnection(UserMailbox $mailbox): array
    {
        if (! $this->imapAvailable()) {
            return [
                'ok' => false,
                'message' => 'The PHP IMAP extension is not installed on this server.',
            ];
        }

        if (! $this->isConfigured($mailbox)) {
            return [
                'ok' => false,
                'message' => 'Add the mailbox host, username, and password before testing the connection.',
            ];
        }

        $connection = $this->openMailbox($mailbox, $mailbox->inbox_folder ?: 'INBOX');

        if (! $connection) {
            return [
                'ok' => false,
                'message' => $this->lastImapError() ?: 'Unable to connect to the mailbox with the current IMAP settings.',
            ];
        }

        @imap_close($connection);

        return [
            'ok' => true,
            'message' => 'Mailbox connection verified successfully.',
        ];
    }

    public function fetchFolderCounts(UserMailbox $mailbox): array
    {
        $counts = [
            self::FOLDER_ALL => 0,
            self::FOLDER_INBOX => 0,
            self::FOLDER_SPAM => 0,
            self::FOLDER_SENT => 0,
            'unread' => 0,
        ];

        foreach ($this->folderMap($mailbox) as $folderKey => $folderName) {
            if (blank($folderName)) {
                continue;
            }

            $connection = $this->openMailbox($mailbox, $folderName);

            if (! $connection) {
                continue;
            }

            $folderCount = (int) @imap_num_msg($connection);
            $unreadCount = count(@imap_search($connection, 'UNSEEN', SE_UID) ?: []);

            $counts[$folderKey] = $folderCount;
            $counts[self::FOLDER_ALL] += $folderCount;
            $counts['unread'] += $unreadCount;

            @imap_close($connection);
        }

        return $counts;
    }

    public function fetchMessages(
        UserMailbox $mailbox,
        string $folderFilter = self::FOLDER_ALL,
        string $readFilter = 'all',
        string $search = '',
        int $limit = 40,
    ): array {
        $messages = [];
        $search = trim($search);
        $perFolderLimit = $folderFilter === self::FOLDER_ALL ? max($limit, 25) : max($limit, 40);

        foreach ($this->requestedFolders($mailbox, $folderFilter) as $folderKey => $folderName) {
            $connection = $this->openMailbox($mailbox, $folderName);

            if (! $connection) {
                continue;
            }

            $uids = @imap_sort($connection, SORTDATE, 1, SE_UID) ?: [];

            foreach (array_slice($uids, 0, $perFolderLimit) as $uid) {
                $summary = $this->buildMessageSummary($connection, $folderKey, $folderName, (string) $uid);

                if (! $summary) {
                    continue;
                }

                if ($readFilter === 'unread' && $summary['is_read']) {
                    continue;
                }

                if ($readFilter === 'read' && ! $summary['is_read']) {
                    continue;
                }

                if ($search !== '' && ! $this->matchesSearch($summary, $search)) {
                    continue;
                }

                $messages[] = $summary;
            }

            @imap_close($connection);
        }

        usort($messages, function (array $left, array $right): int {
            return strcmp($right['received_at'] ?? '', $left['received_at'] ?? '');
        });

        return array_slice($messages, 0, $limit);
    }

    public function fetchMessage(UserMailbox $mailbox, string $folderKey, string $uid): ?array
    {
        $folderName = $this->folderMap($mailbox)[$folderKey] ?? null;

        if (blank($folderName)) {
            return null;
        }

        $connection = $this->openMailbox($mailbox, $folderName);

        if (! $connection) {
            return null;
        }

        $overviewItems = @imap_fetch_overview($connection, $uid, FT_UID) ?: [];
        $overview = $overviewItems[0] ?? null;

        if (! $overview) {
            @imap_close($connection);

            return null;
        }

        $structure = @imap_fetchstructure($connection, $uid, FT_UID);
        $body = $this->extractBody($connection, $uid, $structure);
        $attachments = $this->extractAttachmentMetadata($structure);
        $receivedAt = $this->parseReceivedAt($overview->date ?? null);

        @imap_close($connection);

        return [
            'uid' => (string) $uid,
            'folder_key' => $folderKey,
            'folder_name' => $folderName,
            'subject' => $this->decodeHeader((string) ($overview->subject ?? '(No subject)')),
            'from_name' => $this->firstAddress((string) ($overview->from ?? null))['name'] ?? null,
            'from_email' => $this->firstAddress((string) ($overview->from ?? null))['email'] ?? null,
            'to' => $this->addressEmails((string) ($overview->to ?? null)),
            'cc' => $this->addressEmails((string) ($overview->cc ?? null)),
            'received_at' => $receivedAt?->toIso8601String(),
            'received_label' => $receivedAt?->format('d M Y, h:i A') ?? 'Unknown',
            'body_html' => $body['html'],
            'body_text' => $body['text'],
            'is_read' => ! ((bool) ($overview->unseen ?? false)),
            'attachments' => $attachments,
        ];
    }

    public function downloadAttachment(UserMailbox $mailbox, string $folderKey, string $uid, string $partNumber): ?array
    {
        $folderName = $this->folderMap($mailbox)[$folderKey] ?? null;

        if (blank($folderName)) {
            return null;
        }

        $connection = $this->openMailbox($mailbox, $folderName);

        if (! $connection) {
            return null;
        }

        $structure = @imap_fetchstructure($connection, $uid, FT_UID);
        $part = $this->partByNumber($structure, $partNumber);

        if (! $part) {
            @imap_close($connection);

            return null;
        }

        $body = @imap_fetchbody($connection, $uid, $partNumber, FT_UID | FT_PEEK) ?: '';
        $content = match ((int) ($part->encoding ?? 0)) {
            3 => base64_decode($body, true) ?: '',
            4 => quoted_printable_decode($body),
            default => $body,
        };

        @imap_close($connection);

        return [
            'name' => $this->partFilename($part) ?: 'attachment.bin',
            'mime' => $this->partMimeType($part),
            'content' => $content,
        ];
    }

    public function sendMessage(UserMailbox $mailbox, array $data): void
    {
        $this->applySmtpConfig($mailbox);

        $to = $this->normalizeRecipients($data['to'] ?? '');
        $cc = $this->normalizeRecipients($data['cc'] ?? '');
        $bcc = $this->normalizeRecipients($data['bcc'] ?? '');
        $subject = trim((string) ($data['subject'] ?? ''));
        $body = trim((string) ($data['body'] ?? ''));
        $html = nl2br(e($body));
        $attachments = collect($data['attachments'] ?? [])
            ->filter(fn (array $attachment): bool => filled($attachment['name'] ?? null) && array_key_exists('content', $attachment))
            ->values()
            ->all();

        Mail::mailer('user_mailbox')->html($html, function ($message) use ($mailbox, $to, $cc, $bcc, $subject, $attachments): void {
            $message->to($to)->subject($subject);

            if ($cc !== []) {
                $message->cc($cc);
            }

            if ($bcc !== []) {
                $message->bcc($bcc);
            }

            $fromAddress = $mailbox->from_address ?: ($mailbox->smtp_username ?: $mailbox->imap_username);
            $fromName = $mailbox->from_name ?: ($mailbox->user?->name ?: 'Verification Mailbox');

            if ($fromAddress) {
                $message->from($fromAddress, $fromName);
            }

            foreach ($attachments as $attachment) {
                $message->attachData(
                    $attachment['content'],
                    $attachment['name'],
                    ['mime' => $attachment['mime'] ?? 'application/octet-stream'],
                );
            }
        });
    }

    protected function applySmtpConfig(UserMailbox $mailbox): void
    {
        $encryption = strtolower((string) ($mailbox->smtp_encryption ?: 'ssl'));
        $scheme = match ($encryption) {
            'ssl' => 'smtps',
            'tls' => 'smtp',
            default => null,
        };

        Config::set('mail.mailers.user_mailbox', [
            'transport' => 'smtp',
            'scheme' => $scheme,
            'host' => $mailbox->smtp_host ?: $mailbox->imap_host,
            'port' => $mailbox->smtp_port ?: 465,
            'username' => $mailbox->smtp_username ?: $mailbox->imap_username,
            'password' => $mailbox->smtp_password ?: $mailbox->imap_password,
            'timeout' => 15,
        ]);
    }

    protected function requestedFolders(UserMailbox $mailbox, string $folderFilter): array
    {
        $folders = $this->folderMap($mailbox);

        if ($folderFilter === self::FOLDER_ALL) {
            return $folders;
        }

        return array_filter(
            [$folderFilter => $folders[$folderFilter] ?? null],
            fn (?string $folder): bool => filled($folder),
        );
    }

    protected function folderMap(UserMailbox $mailbox): array
    {
        return array_filter([
            self::FOLDER_INBOX => $mailbox->inbox_folder ?: 'INBOX',
            self::FOLDER_SPAM => $mailbox->spam_folder ?: null,
            self::FOLDER_SENT => $mailbox->sent_folder ?: null,
        ]);
    }

    protected function buildMessageSummary($connection, string $folderKey, string $folderName, string $uid): ?array
    {
        $overviewItems = @imap_fetch_overview($connection, $uid, FT_UID) ?: [];
        $overview = $overviewItems[0] ?? null;

        if (! $overview) {
            return null;
        }

        $from = $this->firstAddress((string) ($overview->from ?? null));
        $receivedAt = $this->parseReceivedAt($overview->date ?? null);
        $preview = Str::limit($this->decodeHeader((string) ($overview->subject ?? '(No subject)')), 160);

        return [
            'uid' => $uid,
            'folder_key' => $folderKey,
            'folder_name' => $folderName,
            'subject' => $this->decodeHeader((string) ($overview->subject ?? '(No subject)')),
            'from_name' => $from['name'] ?? null,
            'from_email' => $from['email'] ?? null,
            'snippet' => $preview,
            'received_at' => $receivedAt?->toIso8601String(),
            'received_label' => $receivedAt?->format('d M Y, h:i A') ?? 'Unknown',
            'short_received_label' => $receivedAt?->format('d M') ?? 'Unknown',
            'is_read' => ! ((bool) ($overview->unseen ?? false)),
            'has_attachments' => false,
            'attachment_count' => 0,
        ];
    }

    protected function matchesSearch(array $summary, string $search): bool
    {
        $needle = Str::lower($search);

        return Str::contains(Str::lower((string) ($summary['subject'] ?? '')), $needle)
            || Str::contains(Str::lower((string) ($summary['from_name'] ?? '')), $needle)
            || Str::contains(Str::lower((string) ($summary['from_email'] ?? '')), $needle)
            || Str::contains(Str::lower((string) ($summary['snippet'] ?? '')), $needle);
    }

    protected function extractBody($connection, string $uid, $structure): array
    {
        $html = null;
        $text = null;

        if ($structure) {
            $parts = $this->flattenParts($structure);

            foreach ($parts as $item) {
                $part = $item['part'];
                $partNumber = $item['part_number'];
                $type = (int) ($part->type ?? 0);
                $subtype = strtolower((string) ($part->subtype ?? ''));

                if ($type !== 0) {
                    continue;
                }

                if ($subtype === 'html' && $html === null) {
                    $html = $this->decodePartBody($connection, $uid, $partNumber, $part);
                }

                if ($subtype === 'plain' && $text === null) {
                    $text = $this->decodePartBody($connection, $uid, $partNumber, $part);
                }
            }
        }

        if ($html === null) {
            $html = $text ? nl2br(e($text)) : null;
        }

        if ($text === null && $html !== null) {
            $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
        }

        if ($html === null && $text === null) {
            $rawBody = @imap_body($connection, $uid, FT_UID | FT_PEEK) ?: '';
            $text = trim(preg_replace('/\s+/', ' ', strip_tags(quoted_printable_decode($rawBody))));
            $html = nl2br(e($text));
        }

        return [
            'html' => $html,
            'text' => $text,
        ];
    }

    protected function decodePartBody($connection, string $uid, string $partNumber, $part): string
    {
        $body = @imap_fetchbody($connection, $uid, $partNumber, FT_UID | FT_PEEK) ?: '';

        return match ((int) ($part->encoding ?? 0)) {
            3 => base64_decode($body, true) ?: '',
            4 => quoted_printable_decode($body),
            default => $body,
        };
    }

    protected function extractAttachmentMetadata($structure): array
    {
        if (! $structure) {
            return [];
        }

        $attachments = [];

        foreach ($this->flattenParts($structure) as $item) {
            $part = $item['part'];
            $partNumber = $item['part_number'];
            $disposition = strtolower((string) ($part->disposition ?? ''));
            $filename = $this->partFilename($part);

            if ($filename === null && ! in_array($disposition, ['attachment', 'inline'], true)) {
                continue;
            }

            $attachments[] = [
                'name' => $filename ?: 'Attachment ' . ($index + 1),
                'size' => (int) ($part->bytes ?? 0),
                'size_label' => $this->formatBytes((int) ($part->bytes ?? 0)),
                'inline' => $disposition === 'inline',
                'part_number' => $partNumber,
                'mime_type' => $this->partMimeType($part),
            ];
        }

        return $attachments;
    }

    protected function hasAttachments($structure): bool
    {
        return $this->extractAttachmentMetadata($structure) !== [];
    }

    protected function partFilename($part): ?string
    {
        foreach (['dparameters', 'parameters'] as $property) {
            foreach ($part->{$property} ?? [] as $parameter) {
                $attribute = strtolower((string) ($parameter->attribute ?? ''));

                if (in_array($attribute, ['filename', 'name'], true)) {
                    return $this->decodeHeader((string) ($parameter->value ?? ''));
                }
            }
        }

        return null;
    }

    protected function partMimeType($part): string
    {
        $primary = match ((int) ($part->type ?? 0)) {
            0 => 'text',
            1 => 'multipart',
            2 => 'message',
            3 => 'application',
            4 => 'audio',
            5 => 'image',
            6 => 'video',
            default => 'application',
        };

        $subtype = strtolower((string) ($part->subtype ?? 'octet-stream'));

        return $primary . '/' . $subtype;
    }

    protected function partByNumber($structure, string $partNumber)
    {
        foreach ($this->flattenParts($structure) as $item) {
            if ($item['part_number'] === $partNumber) {
                return $item['part'];
            }
        }

        return null;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes / 1048576, 1) . ' MB';
    }

    protected function flattenParts($structure, string $prefix = ''): array
    {
        if (! $structure || empty($structure->parts)) {
            return [];
        }

        $flattened = [];

        foreach ($structure->parts as $index => $part) {
            $partNumber = $prefix === '' ? (string) ($index + 1) : $prefix . '.' . ($index + 1);

            $flattened[] = [
                'part_number' => $partNumber,
                'part' => $part,
            ];

            if (! empty($part->parts)) {
                $flattened = array_merge($flattened, $this->flattenParts($part, $partNumber));
            }
        }

        return $flattened;
    }

    protected function normalizeRecipients(string $raw): array
    {
        return collect(explode(',', $raw))
            ->map(fn (string $email): string => trim($email))
            ->filter()
            ->values()
            ->all();
    }

    protected function firstAddress(?string $raw): array
    {
        if (! function_exists('imap_rfc822_parse_adrlist') || blank($raw)) {
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
            'name' => $this->decodeHeader((string) ($address->personal ?? '')) ?: null,
            'email' => ($mailbox && $host && $host !== '.SYNTAX-ERROR.') ? $mailbox . '@' . $host : null,
        ];
    }

    protected function addressEmails(?string $raw): array
    {
        if (! function_exists('imap_rfc822_parse_adrlist') || blank($raw)) {
            return [];
        }

        return collect(@imap_rfc822_parse_adrlist($raw, '') ?: [])
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

    protected function decodeHeader(string $value): string
    {
        if (! function_exists('imap_mime_header_decode')) {
            return $value;
        }

        $segments = @imap_mime_header_decode($value) ?: [];

        return collect($segments)->map(fn ($segment): string => (string) ($segment->text ?? ''))->implode('');
    }

    protected function parseReceivedAt(?string $date): ?Carbon
    {
        try {
            return $date ? Carbon::parse($date) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function openMailbox(UserMailbox $mailbox, string $folderName)
    {
        return @imap_open(
            $this->mailboxPath($mailbox, $folderName),
            (string) $mailbox->imap_username,
            (string) $mailbox->imap_password,
        );
    }

    protected function mailboxPath(UserMailbox $mailbox, string $folderName): string
    {
        $flags = ['/imap'];
        $encryption = strtolower((string) ($mailbox->imap_encryption ?: 'ssl'));

        if ($encryption === 'ssl') {
            $flags[] = '/ssl';
        } elseif ($encryption === 'tls') {
            $flags[] = '/tls';
        } else {
            $flags[] = '/notls';
        }

        if (! $mailbox->imap_validate_certificate) {
            $flags[] = '/novalidate-cert';
        }

        return '{' . $mailbox->imap_host . ':' . ($mailbox->imap_port ?: 993) . implode('', $flags) . '}' . $folderName;
    }

    protected function lastImapError(): ?string
    {
        if (! function_exists('imap_last_error')) {
            return null;
        }

        return @imap_last_error() ?: null;
    }
}
