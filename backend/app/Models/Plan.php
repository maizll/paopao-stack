<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * 套餐预留。MVP 只播种 free，配额不强制执行。
 */
#[Fillable(['code', 'name', 'quotas', 'is_default'])]
class Plan extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quotas' => 'array',
            'is_default' => 'boolean',
        ];
    }
}
