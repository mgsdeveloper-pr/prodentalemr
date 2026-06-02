<?php

namespace App\Support;

use App\Models\PatientStatement;
use App\Models\SaasSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ClinicStatementNotifications
{
    public static function canSend(PatientStatement $statement): bool
    {
        $recipient = static::recipient($statement);

        if (blank($recipient)) {
            return false;
        }

        return SaasMailSettings::canSend(SaasSetting::current()->toArray());
    }

    public static function send(PatientStatement $statement, ?User $actor = null): bool
    {
        $statement->loadMissing(['patient', 'clinic', 'location']);

        $recipient = static::recipient($statement);

        if (blank($recipient)) {
            return false;
        }

        $settings = SaasSetting::current()->toArray();

        if (! SaasMailSettings::canSend($settings)) {
            return false;
        }

        SaasMailSettings::apply($settings);

        $subject = 'Patient statement ' . $statement->statement_number;
        $body = implode("\n", array_filter([
            'Dear ' . ($statement->patient?->full_name ?? 'Patient') . ',',
            '',
            'Please find your latest patient statement attached for review.',
            'Statement Number: ' . $statement->statement_number,
            'Statement Date: ' . optional($statement->statement_date)?->format('Y-m-d'),
            'Statement Period: ' . optional($statement->period_from)?->format('Y-m-d') . ' to ' . optional($statement->period_to)?->format('Y-m-d'),
            'Closing Balance: $' . number_format((float) $statement->closing_balance, 2),
            '',
            $statement->notes ?: 'If you have questions about this balance, please contact the clinic billing team.',
            '',
            $actor?->name ? 'Sent by: ' . $actor->name : null,
        ]));

        Mail::mailer($settings['email_mailer'] ?? 'smtp')
            ->raw($body, function ($message) use ($recipient, $statement, $subject): void {
                $message->to($recipient)->subject($subject);
                $message->attachData(
                    PatientStatementPdf::output($statement),
                    PatientStatementPdf::fileName($statement),
                    ['mime' => 'application/pdf'],
                );
            });

        $statement->forceFill([
            'recipient_email' => $recipient,
            'sent_at' => now(),
            'last_sent_by' => $actor?->getKey(),
            'status' => in_array($statement->status, ['draft', 'issued'], true) ? 'sent' : $statement->status,
        ])->saveQuietly();

        return true;
    }

    protected static function recipient(PatientStatement $statement): ?string
    {
        return $statement->recipient_email ?: $statement->patient?->email;
    }
}
