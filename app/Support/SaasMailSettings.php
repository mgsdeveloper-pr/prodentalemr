<?php

namespace App\Support;

use App\Models\SaasSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class SaasMailSettings
{
    public static function apply(array $state): void
    {
        $mailer = $state['email_mailer'] ?? 'smtp';
        $encryption = $state['email_encryption'] ?? null;
        $scheme = match ($encryption) {
            'ssl' => 'smtps',
            'tls' => 'smtp',
            default => null,
        };

        Config::set('mail.default', $mailer);
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.scheme', $scheme);
        Config::set('mail.mailers.smtp.host', $state['email_host'] ?? null);
        Config::set('mail.mailers.smtp.port', $state['email_port'] ?? null);
        Config::set('mail.mailers.smtp.username', $state['email_username'] ?? null);
        Config::set('mail.mailers.smtp.password', $state['email_password'] ?? null);
        Config::set('mail.from.address', $state['email_from_address'] ?? ($state['support_email'] ?? config('mail.from.address')));
        Config::set('mail.from.name', $state['email_from_name'] ?? ($state['platform_name'] ?? config('mail.from.name')));
    }

    public static function applyFromSettings(?SaasSetting $settings = null): void
    {
        static::apply(($settings ?? SaasSetting::current())->toArray());
    }

    public static function applyRuntimeDefaultsFromSettings(?SaasSetting $settings = null): void
    {
        $state = ($settings ?? SaasSetting::current())->toArray();

        if (! static::canSend($state)) {
            return;
        }

        static::apply($state);
    }

    public static function canSend(array $state): bool
    {
        if (! ($state['email_enabled'] ?? false)) {
            return false;
        }

        if (($state['email_mailer'] ?? 'smtp') === 'log') {
            return true;
        }

        return filled($state['email_host'] ?? null)
            && filled($state['email_port'] ?? null)
            && filled($state['email_from_address'] ?? ($state['support_email'] ?? null));
    }

    public static function sendTestEmail(array $state, string $recipient): void
    {
        static::apply($state);

        $brandName = $state['platform_name'] ?? 'ProDental EMR';

        Mail::mailer($state['email_mailer'] ?? 'smtp')
            ->raw(
                "This is a test email from {$brandName} Notification Centre.\n\nIf you received this message, the current email configuration is working.",
                function ($message) use ($brandName, $recipient): void {
                    $message
                        ->to($recipient)
                        ->subject("{$brandName} test email");
                },
            );
    }
}
