<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySummary extends Model
{
    protected $fillable = [
        'organization_id','employee_id','room_id','date',
        'work_minutes','idle_minutes','phone_minutes','away_minutes',
        'phone_count','away_count','score'
    ];

    protected $casts = [
        'date'=>'date',
        'work_minutes'=>'integer','idle_minutes'=>'integer',
        'phone_minutes'=>'integer','away_minutes'=>'integer',
        'phone_count'=>'integer','away_count'=>'integer','score'=>'integer',
    ];

    public function organization(){ return $this->belongsTo(Organization::class); }
    public function employee()    { return $this->belongsTo(Employee::class); }
    public function room()        { return $this->belongsTo(Room::class); }

    public function scopeOnDate($q,$date){ return $q->whereDate('date',$date); }
    public function scopeOrg($q,$orgId){ return $q->where('organization_id',$orgId); }
}