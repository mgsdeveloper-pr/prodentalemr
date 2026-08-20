<?php

require __DIR__ . '/../vendor/autoload.php';

$cachedConfig = __DIR__ . '/../bootstrap/cache/config.php';

if (is_file($cachedConfig)) {
    $config = require $cachedConfig;
    $defaultConnection = $config['database']['default'] ?? null;
    $database = $config['database']['connections'][$defaultConnection]['database'] ?? null;

    if ($defaultConnection !== 'sqlite' || $database !== ':memory:') {
        fwrite(STDERR, PHP_EOL);
        fwrite(STDERR, 'Tests blocked: cached Laravel config is not using isolated sqlite :memory: database.' . PHP_EOL);
        fwrite(STDERR, 'Run php artisan optimize:clear, then retry the focused test command.' . PHP_EOL);
        fwrite(STDERR, 'This guard prevents RefreshDatabase from wiping the development database.' . PHP_EOL);
        exit(1);
    }
}
