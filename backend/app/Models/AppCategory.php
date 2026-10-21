<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'name', 'slug', 'sort'])]
class AppCategory extends Model
{
    use BelongsToTenant;

    public function apps(): HasMany
    {
        return $this->hasMany(Software::class, 'category_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sort' => $this->sort,
        ];
    }
}
