<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentRelease extends Model
{
    protected $fillable = [
        'version',
        'channel',
        'platform',
        'artifact_path',
        'artifact_name',
        'checksum_sha256',
        'artifact_size',
        'notes',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'artifact_size' => 'integer',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_active', true)->whereNotNull('published_at');
    }
}
