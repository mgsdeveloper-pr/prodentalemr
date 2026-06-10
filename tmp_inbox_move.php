<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$clinic = App\Models\Clinic::query()
    ->where('clinic_name', 'like', 'Meditya Global Services LLC%')
    ->firstOrFail();

$settings = App\Models\SaasSetting::current();
$fields = [
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
    'verification_inbox_last_synced_at',
    'verification_inbox_last_cleanup_at',
];

$payload = $settings->only($fields);
$payload['clinic_id'] = $clinic->id;

$mailbox = App\Models\VerificationInboxMailbox::query()->updateOrCreate(
    ['clinic_id' => $clinic->id],
    $payload,
);

echo json_encode([
    'created_mailbox_id' => $mailbox->id,
    'clinic_id' => $mailbox->clinic_id,
    'enabled' => $mailbox->verification_inbox_enabled,
    'host' => $mailbox->verification_inbox_host,
    'username' => $mailbox->verification_inbox_username,
], JSON_PRETTY_PRINT);
