<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'organization_id','plan_id','status','trial_ends_at','current_period_end',
        'stripe_id','payment_method'
    ];

    protected $casts = [
        'trial_ends_at'=>'datetime',
        'current_period_end'=>'datetime',
    ];

    public function organization(){ return $this->belongsTo(Organization::class); }
    public function plan()        { return $this->belongsTo(Plan::class); }
}