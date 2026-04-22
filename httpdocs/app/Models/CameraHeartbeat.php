<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CameraHeartbeat extends Model
{
    protected $fillable = [
        'agent_device_id',
        'camera_id',
        'stream_status',
        'analyzer',
        'health_message',
        'last_frame_at',
        'fps',
        'observations',
        'checked_at',
    ];

    protected $casts = [
        'last_frame_at' => 'datetime',
        'fps' => 'float',
        'observations' => 'array',
        'checked_at' => 'datetime',
    ];

    public function agentDevice()
    {
        return $this->belongsTo(AgentDevice::class);
    }

    public function camera()
    {
        return $this->belongsTo(Camera::class);
    }
}
