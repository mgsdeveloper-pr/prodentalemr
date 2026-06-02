<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sections = ['core_details','coverage_matrix','plan_provisions','history','verification_information'];
foreach ($sections as $section) {
    echo "SECTION: {$section}\n";
    $rows = App\Models\VerificationFormQuestion::query()
        ->where('clinic_id', 1)
        ->where('section_key', $section)
        ->where('is_builtin', true)
        ->orderBy('sort_order')
        ->get(['prompt','field_key','secondary_field_key','input_type','secondary_input_type','code']);
    foreach ($rows as $row) {
        echo '- ' . $row->prompt . ' | field=' . var_export($row->field_key, true) . ' | second=' . var_export($row->secondary_field_key, true) . ' | type=' . $row->input_type . PHP_EOL;
    }
    echo PHP_EOL;
}
