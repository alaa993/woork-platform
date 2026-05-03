<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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

    public static function trialDaysFor(?Plan $plan = null): ?int
    {
        $configuredDays = config('woork.trial_days');

        if ($configuredDays !== null && $configuredDays !== '') {
            $configuredDays = (int) $configuredDays;

            return $configuredDays > 0 ? $configuredDays : null;
        }

        $planDays = (int) ($plan?->trial_days ?? 14);

        return $planDays > 0 ? $planDays : null;
    }

    public static function trialEndsAtFor(?Plan $plan = null): ?Carbon
    {
        $trialDays = static::trialDaysFor($plan);

        return $trialDays ? now()->addDays($trialDays) : null;
    }

    public function isCurrentlyActive(): bool
    {
        if (in_array($this->status, ['trial', 'trialing'], true)) {
            if (static::trialDaysFor($this->plan) === null) {
                return true;
            }

            return ! $this->trial_ends_at || $this->trial_ends_at->isFuture();
        }

        return $this->status === 'active'
            && (! $this->current_period_end || $this->current_period_end->isFuture());
    }
}
