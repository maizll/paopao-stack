<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'app_id',
    'version_name',
    'version_code',
    'channel',
    'size',
    'checksum',
    'min_os',
    'changelog',
    'storage_key',
    'status',
    'released_at',
])]
class AppVersion extends Model
{
    use BelongsToTenant;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'released_at' => 'datetime',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(Software::class, 'app_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toSummaryArray(): array
    {
        return [
            'id' => $this->id,
            'version_name' => $this->version_name,
            'version_code' => $this->version_code,
            'channel' => $this->channel,
            'released_at' => $this->released_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetailArray(): array
    {
        return [
            ...$this->toSummaryArray(),
            'app_id' => $this->app_id,
            'size' => $this->size,
            'checksum' => $this->checksum,
            'min_os' => $this->min_os,
            'changelog' => $this->changelog,
            'status' => $this->status,
        ];
    }
}
