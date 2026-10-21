<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tenant_id',
    'image',
    'title',
    'link_type',
    'link_value',
    'sort',
    'status',
    'start_at',
    'end_at',
])]
class OpsBanner extends Model
{
    use BelongsToTenant;

    protected $table = 'ops_banners';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image' => $this->image,
            'link_type' => $this->link_type,
            'link_value' => $this->link_value,
            'sort' => $this->sort,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
        ];
    }
}
