<?php

namespace Tests\Feature;

use App\Models\AgentDevice;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAgentRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_pairs_pending_device(): void
    {
        [$organization, $pending] = $this->createPendingDevice();

        $response = $this->postJson('/api/agent/register', [
            'pairing_token' => $pending->pairing_token,
            'device_uuid' => 'woork-test-device-001',
            'name' => 'Branch PC',
            'version' => '1.0.0',
            'os' => 'windows',
            'capabilities' => ['offline_queue' => true],
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['token', 'organization_id', 'agent_device_id']);

        $pending->refresh();
        $this->assertSame('woork-test-device-001', $pending->device_uuid);
        $this->assertSame('online', $pending->status);
        $this->assertNotNull($pending->api_token_hash);
    }

    public function test_register_reuses_existing_device_in_same_organization(): void
    {
        [$organization, $pending] = $this->createPendingDevice();

        $existing = AgentDevice::create([
            'organization_id' => $organization->id,
            'name' => 'Existing Agent',
            'device_uuid' => 'woork-reused-device',
            'pairing_token' => 'PAIR-EXISTING-001',
            'status' => 'online',
            'version' => '1.0.0',
            'os' => 'windows',
            'api_token_hash' => hash('sha256', 'old-token'),
            'is_active' => true,
        ]);

        $pairingToken = $pending->pairing_token;

        $response = $this->postJson('/api/agent/register', [
            'pairing_token' => $pairingToken,
            'device_uuid' => 'woork-reused-device',
            'name' => 'Reinstalled PC',
            'version' => '1.0.1',
            'os' => 'windows',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('agent_device_id', $existing->id)
            ->assertJsonPath('reused_existing_device', true);

        $existing->refresh();
        $pending->refresh();

        $this->assertSame('Reinstalled PC', $existing->name);
        $this->assertSame('woork-reused-device', $existing->device_uuid);
        $this->assertSame($pairingToken, $existing->pairing_token);
        $this->assertTrue($existing->is_active);

        $this->assertFalse($pending->is_active);
        $this->assertSame('replaced', $pending->status);
        $this->assertStringStartsWith('replaced-', $pending->device_uuid);
        $this->assertStringStartsWith('replaced-', $pending->pairing_token);
    }

    public function test_register_returns_conflict_for_other_organization(): void
    {
        [$organizationA, $pending] = $this->createPendingDevice();
        [$organizationB] = $this->createPendingDevice();

        AgentDevice::create([
            'organization_id' => $organizationB->id,
            'name' => 'Other Org Agent',
            'device_uuid' => 'woork-shared-hardware',
            'pairing_token' => 'PAIR-OTHER-ORG',
            'status' => 'online',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/agent/register', [
            'pairing_token' => $pending->pairing_token,
            'device_uuid' => 'woork-shared-hardware',
            'name' => 'Branch PC',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('ok', false)
            ->assertJsonFragment([
                'error' => 'This device UUID is already paired to another organization.',
            ]);
    }

    /**
     * @return array{0: Organization, 1: AgentDevice}
     */
    protected function createPendingDevice(): array
    {
        $plan = Plan::create([
            'slug' => 'basic-'.uniqid(),
            'name' => 'Basic',
            'cameras_limit' => 3,
            'employees_limit' => 15,
            'price_monthly' => 29,
            'price_yearly' => 299,
            'trial_days' => 14,
            'features' => ['agent_devices_limit' => 5],
        ]);

        $organization = Organization::create([
            'name' => 'Test Org '.uniqid(),
            'email' => uniqid('org', true).'@test.local',
            'language' => 'en',
            'plan_id' => $plan->id,
            'company_type' => 'company',
        ]);

        Subscription::create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $pending = AgentDevice::create([
            'organization_id' => $organization->id,
            'name' => 'Pending Agent',
            'device_uuid' => 'pending-'.uniqid(),
            'pairing_token' => 'PAIR-'.strtoupper(uniqid()),
            'status' => 'pending',
            'is_active' => true,
        ]);

        return [$organization, $pending];
    }
}
