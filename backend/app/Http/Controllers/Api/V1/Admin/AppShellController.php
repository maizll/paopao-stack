<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AuditLog;
use App\Models\SiteSetting;
use App\Support\AppShell;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppShellController extends ApiController
{
    public function show(): JsonResponse
    {
        return $this->ok(AppShell::present(AppShell::current()));
    }

    public function update(Request $request): JsonResponse
    {
        $shell = AppShell::normalize($request->all());
        $setting = SiteSetting::query()->first() ?? new SiteSetting;
        $setting->shell = $shell;
        $setting->save();

        $labels = array_map(fn (string $id) => AppShell::MODULES[$id]['label'], $shell['modules']);
        foreach ($shell['customPages'] as $page) {
            $labels[] = $page['label'];
        }
        AuditLog::query()->create([
            'actor_id' => $request->user()?->id,
            'action' => 'shell.update',
            'target_type' => 'setting',
            'target_id' => $setting->id,
            'summary' => '更新 App 形态：'.implode('、', $labels).'，底栏'.AppShell::TABBARS[$shell['tabbar']],
        ]);

        return $this->ok(AppShell::present($shell));
    }
}
