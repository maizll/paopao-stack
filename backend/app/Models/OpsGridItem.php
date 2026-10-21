<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tenant_id',
    'title',
    'icon',
    'link_type',
    'link_value',
    'badge',
    'sort',
    'status',
])]
class OpsGridItem extends Model
{
    use BelongsToTenant;

    protected $table = 'ops_grid_items';

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'icon' => $this->icon,
            'link_type' => $this->link_type,
            'link_value' => $this->link_value,
            'badge' => $this->badge,
            'sort' => $this->sort,
        ];
    }
}
