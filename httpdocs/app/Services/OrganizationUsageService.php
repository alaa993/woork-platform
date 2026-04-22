<?php

namespace App\Services;

use App\Models\Organization;

class OrganizationUsageService
{
    public function summary(Organization $organization): array
    {
        return [
            'plan' => $organization->currentPlan(),
            'subscription_active' => $organization->isActive(),
            'cameras' => $this->resource($organization, 'cameras'),
            'employees' => $this->resource($organization, 'employees'),
            'agent_devices' => $this->resource($organization, 'agent_devices'),
        ];
    }

    public function resource(Organization $organization, string $resource): array
    {
        $used = $organization->usageCount($resource);
        $limit = $organization->limitFor($resource);
        $unlimited = $limit === null || $limit < 1;

        return [
            'key' => $resource,
            'used' => $used,
            'limit' => $limit,
            'unlimited' => $unlimited,
            'remaining' => $unlimited ? null : max(0, $limit - $used),
            'at_limit' => ! $unlimited && $used >= $limit,
        ];
    }

    public function canCreate(Organization $organization, string $resource): bool
    {
        return ! $this->resource($organization, $resource)['at_limit'];
    }
}
