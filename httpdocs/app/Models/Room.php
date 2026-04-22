<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['organization_id','name','location','notes'];

    public function organization(){ return $this->belongsTo(Organization::class); }
    public function cameras()     { return $this->hasMany(Camera::class); }
    public function employees()   { return $this->hasMany(Employee::class); }
    public function events()      { return $this->hasMany(Event::class); }

    public function scopeOrg($q,$orgId){ return $q->where('organization_id',$orgId); }
}