<?php

namespace App\Http\Requests\Posts;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
            'board_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:80'],
            'content' => ['required', 'string', 'max:20000'],
            'cover' => ['nullable', 'string', 'max:255'],
            'app_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'board_id' => '版块',
            'title' => '标题',
            'content' => '正文',
        ];
    }
}
