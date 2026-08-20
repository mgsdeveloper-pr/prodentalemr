<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->guardAgainstCachedDevelopmentDatabaseTesting();

        parent::setUp();

        $this->guardAgainstDevelopmentDatabaseTesting();
    }

    protected function guardAgainstCachedDevelopmentDatabaseTesting(): void
    {
        $cachedConfig = dirname(__DIR__) . '/bootstrap/cache/config.php';

        if (! is_file($cachedConfig)) {
            return;
        }

        $config = require $cachedConfig;
        $connection = $config['database']['default'] ?? null;
        $database = $config['database']['connections'][$connection]['database'] ?? null;

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            $this->fail(
                'Tests blocked: cached Laravel config is not using isolated sqlite :memory:. ' .
                'Run php artisan optimize:clear before running tests.'
            );
        }
    }

    protected function guardAgainstDevelopmentDatabaseTesting(): void
    {
        $connection = config('database.default') ?: getenv('DB_CONNECTION');
        $database = config("database.connections.{$connection}.database") ?: getenv('DB_DATABASE');

        if (app()->environment('testing') && ($connection !== 'sqlite' || $database !== ':memory:')) {
            $this->fail(
                'Tests blocked: the active database is not isolated sqlite :memory:. ' .
                'Running RefreshDatabase here could wipe development data.'
            );
        }
    }
}
