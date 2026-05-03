<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AgentRelease extends Model
{
    public const SUPPORTED_PLATFORMS = [
        'windows-x64' => [
            'label' => 'Windows 10/11 64-bit',
            'description' => 'Recommended for most customer PCs.',
        ],
        'windows-x86' => [
            'label' => 'Windows 10 32-bit',
            'description' => 'For older 32-bit Windows 10 machines.',
        ],
        'windows-7-legacy' => [
            'label' => 'Windows 7 Legacy',
            'description' => 'Compatibility build for legacy PCs only.',
        ],
    ];

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

    public static function supportedPlatforms(): array
    {
        return self::SUPPORTED_PLATFORMS;
    }

    public static function supportedPlatformKeys(): array
    {
        return array_keys(self::SUPPORTED_PLATFORMS);
    }

    public static function platformMeta(string $platform): array
    {
        return self::SUPPORTED_PLATFORMS[$platform] ?? [
            'label' => $platform,
            'description' => null,
        ];
    }

    public static function platformLabel(string $platform): string
    {
        return static::platformMeta($platform)['label'];
    }

    public static function publishedStableByPlatform(): Collection
    {
        return static::query()
            ->published()
            ->where('channel', 'stable')
            ->whereIn('platform', static::supportedPlatformKeys())
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->unique('platform')
            ->values();
    }
}
