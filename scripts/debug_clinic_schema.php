<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$clinicTable = Illuminate\Support\Facades\Schema::getColumnListing('clinics');
echo 'Clinic columns: ' . implode(', ', $clinicTable) . PHP_EOL . PHP_EOL;

$clinics = Illuminate\Support\Facades\DB::table('clinics')->limit(10)->get();
foreach ($clinics as $clinic) {
    echo json_encode($clinic, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

echo "\nQuestion counts by clinic_id:\n";
$rows = Illuminate\Support\Facades\DB::table('verification_form_questions')
    ->selectRaw('clinic_id, count(*) as aggregate')
    ->groupBy('clinic_id')
    ->orderBy('clinic_id')
    ->get();
foreach ($rows as $row) {
    echo var_export($row->clinic_id, true) . ' => ' . $row->aggregate . PHP_EOL;
}
