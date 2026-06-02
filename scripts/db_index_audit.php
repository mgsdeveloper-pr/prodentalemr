<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select('SHOW TABLES');
$key = array_key_first((array) $tables[0] ?? []);
foreach ($tables as $row) {
    $table = $row->$key;
    echo "TABLE: {$table}\n";
    $indexes = DB::select("SHOW INDEX FROM `{$table}`");
    foreach ($indexes as $index) {
        echo '  ' . $index->Key_name . ' | col=' . $index->Column_name . ' | non_unique=' . $index->Non_unique . PHP_EOL;
    }
    echo PHP_EOL;
}
