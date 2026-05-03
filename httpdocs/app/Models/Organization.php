<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = ['name','company_type','email','country','language','plan_id','owner_user_id'];

    public function plan()        { return $this->belongsTo(Plan::class); }
    public function users()       { return $this->hasMany(User::class); }
    public function rooms()       { return $this->hasMany(Room::class); }
    public function cameras()     { return $this->hasMany(Camera::class); }
    public function agentDevices(){ return $this->hasMany(AgentDevice::class); }
    public function employees()   { return $this->hasMany(Employee::class); }
    public function events()      { return $this->hasMany(Event::class); }
    public function summaries()   { return $this->hasMany(DailySummary::class); }
    public function alerts()      { return $this->hasMany(Alert::class); }
    public function policies()    { return $this->hasMany(Policy::class); }
    public function subscriptions(){ return $this->hasMany(Subscription::class); }
    public function subscription(){ return $this->hasOne(Subscription::class)->latestOfMany(); }

    // Helper scope
    public function scopeOrg($q, $orgId){ return $q->where('organization_id', $orgId); }
	
    public function currentPlan(): ?Plan
    {
        return $this->subscription?->plan ?: $this->plan;
    }

    public function isActive(): bool
    {
        $subscription = $this->subscription;

        return $subscription?->isCurrentlyActive() ?? false;
    }

    public function limitFor(string $resource): ?int
    {
        $plan = $this->currentPlan();
        if (! $plan) {
            return null;
        }

        return match ($resource) {
            'cameras' => $plan->cameras_limit,
            'employees' => $plan->employees_limit,
            'agent_devices' => $plan->features['agent_devices_limit']
                ?? $plan->features['devices_limit']
                ?? $plan->cameras_limit,
            default => null,
        };
    }

    public function usageCount(string $resource): int
    {
        return match ($resource) {
            'cameras' => $this->cameras()->count(),
            'employees' => $this->employees()->count(),
            'agent_devices' => $this->agentDevices()->count(),
            default => 0,
        };
    }

    public function canCreateResource(string $resource): bool
    {
        $limit = $this->limitFor($resource);

        return $limit === null || $limit < 1 || $this->usageCount($resource) < $limit;
    }
}
