<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Clinics:\n";
foreach (App\Models\Clinic::query()->select('id','name','organization_id')->orderBy('id')->get() as $clinic) {
    echo $clinic->id . ' | ' . $clinic->name . ' | org ' . $clinic->organization_id . PHP_EOL;
}

echo "\nQuestion counts by clinic_id:\n";
$rows = App\Models\VerificationFormQuestion::query()
    ->selectRaw('clinic_id, count(*) as aggregate')
    ->groupBy('clinic_id')
    ->orderBy('clinic_id')
    ->get();
foreach ($rows as $row) {
    echo var_export($row->clinic_id, true) . ' => ' . $row->aggregate . PHP_EOL;
}
