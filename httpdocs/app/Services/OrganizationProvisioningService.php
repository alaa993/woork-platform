<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrganizationProvisioningService
{
    public function defaultPlan(): Plan
    {
        return Plan::query()
            ->where('is_active', true)
            ->where('slug', 'basic')
            ->first()
            ?? Plan::query()->where('is_active', true)->orderBy('id')->first()
            ?? Plan::query()->orderBy('id')->firstOrFail();
    }

    public function ensureWorkspace(
        User $user,
        string $organizationName,
        ?string $planSlug = null,
        string $companyType = 'company',
        ?string $language = null,
    ): User {
        if ($user->organization_id) {
            return $user->fresh(['organization']);
        }

        return DB::transaction(function () use ($user, $organizationName, $planSlug, $companyType, $language) {
            $plan = $planSlug
                ? Plan::where('slug', $planSlug)->firstOrFail()
                : $this->defaultPlan();

            $trialEndsAt = Subscription::trialEndsAtFor($plan);

            $org = Organization::create([
                'name' => $organizationName,
                'company_type' => $companyType,
                'language' => $language ?? $user->language ?? app()->getLocale(),
                'plan_id' => $plan->id,
                'owner_user_id' => $user->id,
            ]);

            Subscription::create([
                'organization_id' => $org->id,
                'plan_id' => $plan->id,
                'status' => 'trial',
                'trial_ends_at' => $trialEndsAt,
                'current_period_end' => $trialEndsAt,
            ]);

            $user->organization_id = $org->id;
            if ($language) {
                $user->language = $language;
            }
            $user->save();

            return $user->fresh(['organization']);
        });
    }
}
