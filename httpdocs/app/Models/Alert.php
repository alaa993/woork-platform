<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'organization_id','camera_id','agent_device_id','employee_id','room_id','kind','level','channel',
        'source','is_active','resolved_at','rules','message'
    ];

    protected $casts = ['is_active'=>'boolean','resolved_at'=>'datetime','rules'=>'array'];

    public function organization(){ return $this->belongsTo(Organization::class); }
    public function camera()      { return $this->belongsTo(Camera::class); }
    public function agentDevice() { return $this->belongsTo(AgentDevice::class); }
    public function employee()    { return $this->belongsTo(Employee::class); }
    public function room()        { return $this->belongsTo(Room::class); }

    public function scopeActive($q){ return $q->where('is_active',true); }
    public function scopeOrg($q,$orgId){ return $q->where('organization_id',$orgId); }
}
