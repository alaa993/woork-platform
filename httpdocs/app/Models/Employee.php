<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'organization_id','room_id','name','title','photos','is_active'
    ];

    protected $casts = ['photos'=>'array','is_active'=>'boolean'];

    public function organization(){ return $this->belongsTo(Organization::class); }
    public function room()        { return $this->belongsTo(Room::class); }
    public function events()      { return $this->hasMany(Event::class); }
    public function summaries()   { return $this->hasMany(DailySummary::class); }

    public function scopeActive($q){ return $q->where('is_active', true); }
    public function scopeOrg($q,$orgId){ return $q->where('organization_id',$orgId); }
}