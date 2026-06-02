<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$record = App\Models\BillingWorkItem::with(['clinic','organization','verificationProfile'])->find(1);
if (! $record) { echo "NO_RECORD\n"; exit; }
echo "Record clinic_id: " . var_export($record->clinic_id, true) . PHP_EOL;
echo "Clinic: " . optional($record->clinic)->name . PHP_EOL;
echo "Org: " . optional($record->organization)->name . PHP_EOL;
echo "Form type: " . var_export(optional($record->verificationProfile)->form_type, true) . PHP_EOL;

echo "Questions by section for clinic:\n";
$sections = ['core_details','coverage_matrix','plan_provisions','history','frequency_diagnostic_preventative','frequency_basic','frequency_major','frequency_orthodontics_benefit','service_history','verification_information'];
foreach ($sections as $section) {
    $count = App\Models\VerificationFormQuestion::query()
        ->where('clinic_id', $record->clinic_id)
        ->where('is_active', true)
        ->where('section_key', $section)
        ->count();
    echo $section . ': ' . $count . PHP_EOL;
}
