<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    protected $fillable = [
        'organization_id','room_id','save_video','work_hours','breaks','visibility','thresholds'
    ];

    protected $casts = [
        'save_video'=>'boolean',
        'work_hours'=>'array','breaks'=>'array','visibility'=>'array','thresholds'=>'array'
    ];

    public function organization(){ return $this->belongsTo(Organization::class); }
    public function room()        { return $this->belongsTo(Room::class); }
}