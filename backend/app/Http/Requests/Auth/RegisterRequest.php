<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account' => ['required', 'string', 'max:120', $this->accountRule()],
            'password' => ['required', 'string', 'min:8', 'max:64'],
            'nickname' => ['nullable', 'string', 'max:32'],
            'device_id' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'account' => '账号',
            'password' => '密码',
            'nickname' => '昵称',
        ];
    }

    private function accountRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value)) {
                $fail('账号需为邮箱或手机号');

                return;
            }

            $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            $isPhone = preg_match('/^\d{6,20}$/', $value) === 1;

            if (! $isEmail && ! $isPhone) {
                $fail('账号需为邮箱或 6–20 位手机号');
            }
        };
    }
}
