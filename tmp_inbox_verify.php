<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$mailbox = App\Models\VerificationInboxMailbox::query()
    ->with('clinic:id,clinic_name')
    ->where('clinic_id', 1)
    ->first();

echo json_encode($mailbox ? [
    'id' => $mailbox->id,
    'clinic' => $mailbox->clinic?->clinic_name,
    'enabled' => $mailbox->verification_inbox_enabled,
    'provider' => $mailbox->verification_inbox_provider,
    'host' => $mailbox->verification_inbox_host,
    'port' => $mailbox->verification_inbox_port,
    'username' => $mailbox->verification_inbox_username,
    'folder_inbox' => $mailbox->verification_inbox_folder_inbox,
    'folder_spam' => $mailbox->verification_inbox_folder_spam,
] : null, JSON_PRETTY_PRINT);
