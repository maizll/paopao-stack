<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(private readonly AuthService $auth) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->ok($this->auth->register(
            $request->string('account')->toString(),
            $request->string('password')->toString(),
            $request->input('nickname'),
        ));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->ok($this->auth->login(
            $request->string('account')->toString(),
            $request->string('password')->toString(),
        ));
    }

    public function refresh(Request $request): JsonResponse
    {
        return $this->ok($this->auth->refresh($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->ok(null);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok($request->user()->toMeArray());
    }

    public function forgot(): JsonResponse
    {
        return $this->phaseTwo('找回密码');
    }

    public function reset(): JsonResponse
    {
        return $this->phaseTwo('重置密码');
    }
}
