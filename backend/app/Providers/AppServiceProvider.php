<?php

namespace App\Providers;

use App\Support\Install\InstallLock;
use App\Support\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        if ($this->app->runningUnitTests() || InstallLock::exists()) {
            return;
        }

        // 迁移完成前没有 sessions 表，向导只能先用文件会话。
        if (config('session.driver') === 'database') {
            config(['session.driver' => 'file']);
        }
        if (config('cache.default') === 'database') {
            config(['cache.default' => 'file']);
        }
    }
}
