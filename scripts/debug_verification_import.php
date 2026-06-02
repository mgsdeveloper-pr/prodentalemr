<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\Location;
use App\Models\Provider;
use App\Models\User;
use App\Support\VerificationRequestImportService;

$mode = $argv[1] ?? 'inspect';

if ($mode === 'import') {
    $path = $argv[2] ?? null;

    if (! $path) {
        fwrite(STDERR, "Missing import path.\n");
        exit(1);
    }

    $clinic = Clinic::find(1);
    $user = User::find(1);
    $service = app(VerificationRequestImportService::class);
    $result = $service->importFromAbsolutePath($path, $clinic, $user, basename($path));

    echo json_encode($result, JSON_PRETTY_PRINT);
    exit(0);
}

$payload = [
    'clinic' => Clinic::query()->select('id', 'clinic_name', 'organization_id')->find(1),
    'location' => Location::query()->select('id', 'clinic_id', 'location_name')->where('clinic_id', 1)->first(),
    'provider' => Provider::query()->select('id', 'clinic_id', 'location_id', 'display_name')->where('clinic_id', 1)->first(),
    'latest' => BillingWorkItem::query()
        ->with(['verificationProfile', 'verificationPlanSnapshots'])
        ->whereHas('managedBillingService', fn ($query) => $query->where('category', 'verification'))
        ->latest('id')
        ->take(5)
        ->get()
        ->map(fn (BillingWorkItem $item) => [
            'id' => $item->id,
            'reference' => $item->reference_number,
            'title' => $item->title,
            'source' => $item->source,
            'patient_id' => $item->patient_id,
            'provider_id' => $item->provider_id,
            'location_id' => $item->location_id,
            'profile_patient' => $item->verificationProfile?->patient_full_name,
            'profile_provider' => $item->verificationProfile?->provider_name,
            'profile_location' => $item->verificationProfile?->location_name,
            'payer' => $item->verificationPlanSnapshots->first()?->payer_name,
            'created_at' => (string) $item->created_at,
        ])
        ->values(),
];

echo json_encode($payload, JSON_PRETTY_PRINT);
