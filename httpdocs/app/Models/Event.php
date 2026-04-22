<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'organization_id',
        'camera_id',
        'agent_device_id',
        'employee_id',
        'room_id',
        'type',
        'track_id',
        'confidence',
        'started_at',
        'ended_at',
        'duration_seconds',
        'meta',
    ];

    protected $casts = [
        'started_at'=>'datetime',
        'ended_at'  =>'datetime',
        'duration_seconds'=>'integer',
        'confidence' => 'decimal:2',
        'meta' => 'array',
    ];

    public function organization(){ return $this->belongsTo(Organization::class); }
    public function camera()      { return $this->belongsTo(Camera::class); }
    public function agentDevice() { return $this->belongsTo(AgentDevice::class); }
    public function employee()    { return $this->belongsTo(Employee::class); }
    public function room()        { return $this->belongsTo(Room::class); }

    public function scopeToday($q){
        return $q->whereBetween('started_at', [now()->startOfDay(), now()->endOfDay()]);
    }
    public function scopeOrg($q,$orgId){ return $q->where('organization_id',$orgId); }
}
