<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentDevice extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'device_uuid',
        'pairing_token',
        'api_token_hash',
        'status',
        'version',
        'os',
        'last_ip',
        'capabilities',
        'last_seen_at',
        'is_active',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function cameras()
    {
        return $this->hasMany(Camera::class);
    }

    public function heartbeats()
    {
        return $this->hasMany(AgentHeartbeat::class);
    }

    public function cameraHeartbeats()
    {
        return $this->hasMany(CameraHeartbeat::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
