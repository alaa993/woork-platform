<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    protected $fillable = [
        'organization_id',
        'agent_device_id',
        'room_id',
        'name',
        'purpose',
        'analysis_mode',
        'rtsp_url',
        'status',
        'stream_status',
        'health_message',
        'is_enabled',
        'last_seen_at',
        'last_frame_at',
        'roi',
        'analysis_config',
    ];

    protected $casts = [
        'roi' => 'array',
        'analysis_config' => 'array',
        'is_enabled' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_frame_at' => 'datetime',
    ];

    public function organization(){ return $this->belongsTo(Organization::class); }
    public function agentDevice() { return $this->belongsTo(AgentDevice::class); }
    public function room()        { return $this->belongsTo(Room::class); }
    public function heartbeats()  { return $this->hasMany(CameraHeartbeat::class); }
    public function events()      { return $this->hasMany(Event::class); }

    public function scopeOrg($q,$orgId){ return $q->where('organization_id',$orgId); }
}
