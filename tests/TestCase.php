<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require dirname(__DIR__).'/bootstrap/app.php';

        // Never load the developer's .env or connect tests to their database.
        $app->useEnvironmentPath(__DIR__);
        $app->loadEnvironmentFrom('.env.testing');
        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));

        $app->afterBootstrapping(LoadConfiguration::class, function ($app) {
            $app['config']->set([
                'app.env' => 'testing',
                'app.key' => 'base64:'.base64_encode(str_repeat('t', 32)),
                'database.default' => 'sqlite',
                'database.connections.sqlite.url' => null,
                'database.connections.sqlite.database' => ':memory:',
                'session.driver' => 'array',
                'cache.default' => 'array',
                'queue.default' => 'sync',
                'mail.default' => 'array',
                'logging.default' => 'errorlog',
                'view.compiled' => storage_path('framework/testing/views'),
            ]);
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
