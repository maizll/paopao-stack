<?php

namespace Tests;

use App\Support\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $lock = storage_path('framework/testing/install.lock');
        config(['paopao.install_lock' => $lock]);
        if (! is_file($lock)) {
            file_put_contents($lock, '{"testing":true}'.PHP_EOL);
        }

        $this->app->make(TenantContext::class)->forget();
    }
}
