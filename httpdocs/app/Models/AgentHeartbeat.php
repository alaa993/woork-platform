<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentHeartbeat extends Model
{
    protected $fillable = [
        'agent_device_id',
        'status',
        'capabilities',
        'checked_at',
        'ip_address',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'checked_at' => 'datetime',
    ];

    public function agentDevice()
    {
        return $this->belongsTo(AgentDevice::class);
    }
}
