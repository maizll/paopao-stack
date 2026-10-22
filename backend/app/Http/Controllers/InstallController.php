<?php

namespace App\Http\Controllers;

use App\Support\ApiCode;
use App\Support\ApiResponse;
use App\Support\Install\DatabaseProbe;
use App\Support\Install\EnvironmentChecker;
use App\Support\Install\InstallLock;
use App\Support\Install\Installer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class InstallController extends Controller
{
    public function show(): View
    {
        if (InstallLock::exists()) {
            return view('install.complete', [
                'summary' => InstallLock::read(),
            ]);
        }

        return view('install.wizard');
    }

    public function environment(): JsonResponse
    {
        if ($locked = $this->locked()) {
            return $locked;
        }

        return ApiResponse::ok(EnvironmentChecker::check());
    }

    public function testDatabase(Request $request): JsonResponse
    {
        if ($locked = $this->locked()) {
            return $locked;
        }

        $config = $this->databaseInput($request);
        if ($config instanceof JsonResponse) {
            return $config;
        }

        $result = DatabaseProbe::test($config);
        if (! $result['ok']) {
            $request->session()->forget('install.database');

            return ApiResponse::fail(ApiCode::BUSINESS, $result['message'], 422);
        }

        $request->session()->put('install.database', [
            'host' => $config['host'],
            'port' => $config['port'],
            'database' => $config['database'],
            'username' => $config['username'],
            'password' => $config['password'],
            'server' => $result['server'] ?? '',
        ]);

        return ApiResponse::ok([
            'server' => $result['server'] ?? '',
            'created' => (bool) ($result['created'] ?? false),
        ], $result['message']);
    }

    public function saveAdmin(Request $request): JsonResponse
    {
        if ($locked = $this->locked()) {
            return $locked;
        }

        $data = $this->validated($request, [
            'nickname' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'nickname.required' => '请填写管理员昵称。',
            'email.required' => '请填写管理员邮箱。',
            'email.email' => '管理员邮箱格式不正确。',
            'password.required' => '请填写管理员密码。',
            'password.min' => '管理员密码至少 8 位。',
            'password.confirmed' => '两次输入的密码不一致。',
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $request->session()->put('install.admin', [
            'nickname' => $data['nickname'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return ApiResponse::ok([
            'email' => $data['email'],
            'nickname' => $data['nickname'],
        ], '管理员账号已记录。');
    }

    public function saveSite(Request $request): JsonResponse
    {
        if ($locked = $this->locked()) {
            return $locked;
        }

        $data = $this->validated($request, [
            'site_name' => ['required', 'string', 'max:40'],
            'app_url' => ['required', 'url', 'max:200'],
            'tenant_name' => ['required', 'string', 'max:40'],
            'tenant_slug' => ['required', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:32'],
            'seed_demo' => ['sometimes', 'boolean'],
        ], [
            'site_name.required' => '请填写站点名称。',
            'app_url.required' => '请填写站点地址。',
            'app_url.url' => '站点地址需要是完整的 http(s) 链接。',
            'tenant_name.required' => '请填写租户名称。',
            'tenant_slug.required' => '请填写租户标识。',
            'tenant_slug.regex' => '租户标识只能使用小写字母、数字和连字符。',
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $site = [
            'site_name' => $data['site_name'],
            'app_url' => $data['app_url'],
            'tenant_name' => $data['tenant_name'],
            'tenant_slug' => $data['tenant_slug'],
            'seed_demo' => (bool) ($data['seed_demo'] ?? false),
        ];
        $request->session()->put('install.site', $site);

        return ApiResponse::ok($site, '站点信息已记录。');
    }

    public function run(Request $request, Installer $installer): JsonResponse
    {
        if ($locked = $this->locked()) {
            return $locked;
        }

        $database = $request->session()->get('install.database');
        $admin = $request->session()->get('install.admin');
        $site = $request->session()->get('install.site');
        if (! is_array($database) || ! is_array($admin) || ! is_array($site)) {
            return ApiResponse::fail(ApiCode::BUSINESS, '请先完成环境检测、数据库、管理员和站点信息。', 422);
        }

        try {
            $result = $installer->run($database, $admin, $site, true);
        } catch (RuntimeException $exception) {
            return ApiResponse::fail(ApiCode::SERVER, $exception->getMessage(), 500);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::fail(ApiCode::SERVER, '安装没有完成，配置尚未锁定，可以修改后重试。', 500);
        }

        $request->session()->forget(['install.database', 'install.admin', 'install.site']);

        return ApiResponse::ok([
            'log' => $result['log'],
            'health_url' => url('/api/v1/system/health'),
            'admin_url' => 'http://127.0.0.1:43124/auth/login',
            'client_url' => 'http://127.0.0.1:43126',
        ], '安装完成');
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @return array<string, mixed>|JsonResponse
     */
    private function validated(Request $request, array $rules, array $messages): array|JsonResponse
    {
        $validator = validator($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return ApiResponse::fail(ApiCode::VALIDATION, (string) $validator->errors()->first(), 422, [
                'errors' => $validator->errors(),
            ]);
        }

        return $validator->validated();
    }

    private function locked(): ?JsonResponse
    {
        if (! InstallLock::exists()) {
            return null;
        }

        return ApiResponse::fail(ApiCode::CONFLICT, '系统已安装，不能重复执行。', 409);
    }

    /**
     * @return array{host: string, port: int, database: string, username: string, password: string}|JsonResponse
     */
    private function databaseInput(Request $request): array|JsonResponse
    {
        $data = $this->validated($request, [
            'host' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'regex:/^[A-Za-z0-9_]+$/', 'max:64'],
            'username' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'password' => ['nullable', 'string', 'max:128'],
        ], [
            'host.required' => '请填写数据库主机。',
            'host.regex' => '数据库主机只能包含字母、数字、点号和连字符。',
            'port.required' => '请填写数据库端口。',
            'database.required' => '请填写数据库名。',
            'database.regex' => '数据库名只能包含字母、数字和下划线。',
            'username.required' => '请填写数据库账号。',
            'username.regex' => '数据库账号只能包含字母、数字、下划线和连字符。',
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        return [
            'host' => $data['host'],
            'port' => (int) $data['port'],
            'database' => $data['database'],
            'username' => $data['username'],
            'password' => (string) ($data['password'] ?? ''),
        ];
    }
}
