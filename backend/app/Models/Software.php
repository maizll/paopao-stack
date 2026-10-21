<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 软件商品。表名 apps，类名避开应用容器。
 */
#[Fillable([
    'tenant_id',
    'category_id',
    'name',
    'slug',
    'icon',
    'summary',
    'description',
    'download_count',
    'status',
])]
class Software extends Model
{
    use BelongsToTenant;

    protected $table = 'apps';

    public function category(): BelongsTo
    {
        return $this->belongsTo(AppCategory::class, 'category_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AppVersion::class, 'app_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toListArray(): array
    {
        $latest = $this->relationLoaded('versions')
            ? $this->versions->first()
            : $this->versions()->where('status', 'published')->orderByDesc('version_code')->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'summary' => $this->summary,
            'category' => $this->category?->toPublicArray(),
            'latest_version' => $latest?->toSummaryArray(),
            'download_count' => $this->download_count,
            'status' => $this->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetailArray(): array
    {
        return [
            ...$this->toListArray(),
            'description' => $this->description,
        ];
    }
}
