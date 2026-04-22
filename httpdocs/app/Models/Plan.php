<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'slug','name','cameras_limit','employees_limit',
        'price_monthly','price_yearly','trial_days','features','is_active'
    ];
    protected $casts = ['features'=>'array','is_active'=>'boolean'];

    public function organizations(){ return $this->hasMany(Organization::class); }
}