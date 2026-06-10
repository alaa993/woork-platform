<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\OTP\WhatsAppOtp;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_signup_page_lists_active_plans(): void
    {
        $response = $this->get(route('signup'));

        $response->assertOk()
            ->assertSee('Basic')
            ->assertSee('Pro');
    }

    public function test_signup_flow_creates_user_organization_and_subscription(): void
    {
        $phone = '+962791234567';
        $normalized = app(WhatsAppOtp::class)->normalizePhone($phone);

        $this->post(route('signup.submit'), [
            'name' => 'Ahmad Ali',
            'phone' => $phone,
            'email' => 'ahmad@example.com',
            'org_name' => 'Ahmad Co',
            'company_type' => 'company',
            'language' => 'ar',
            'plan' => 'basic',
            'agree' => '1',
        ])->assertOk();

        $code = OtpCode::where('phone', $normalized)->latest('id')->value('code');
        $this->assertNotNull($code);

        $response = $this->post(route('signup.verify'), [
            'phone' => $phone,
            'code' => $code,
        ]);

        $response->assertRedirect(route('app'));

        $user = User::where('phone', app(WhatsAppOtp::class)->normalizePhone($phone))->first();
        $this->assertNotNull($user);
        $this->assertSame('Ahmad Ali', $user->name);
        $this->assertNotNull($user->organization_id);

        $org = Organization::find($user->organization_id);
        $this->assertSame('Ahmad Co', $org->name);
        $this->assertSame('company', $org->company_type);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('subscriptions', [
            'organization_id' => $org->id,
            'status' => 'trial',
        ]);
    }

    public function test_login_otp_register_flow_provisions_workspace(): void
    {
        $phone = '+962799887766';
        $normalized = app(WhatsAppOtp::class)->normalizePhone($phone);

        $this->post(route('otp.request'), ['phone' => $phone])->assertOk();

        $code = OtpCode::where('phone', $normalized)->latest('id')->value('code');
        $this->assertNotNull($code);

        $this->post(route('otp.verify'), [
            'phone' => $phone,
            'code' => $code,
        ])->assertRedirect(route('register.show'));

        $response = $this->post(route('register.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
        ]);

        $response->assertRedirect(route('app'));

        $user = User::where('phone', app(WhatsAppOtp::class)->normalizePhone($phone))->first();
        $this->assertNotNull($user->organization_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_user_login_without_organization_gets_workspace(): void
    {
        $phone = '+962788776655';
        $normalized = app(WhatsAppOtp::class)->normalizePhone($phone);

        User::create([
            'name' => 'Legacy User',
            'phone' => $normalized,
            'role' => User::ROLE_COMPANY_ADMIN,
            'password' => bcrypt('secret'),
        ]);

        $this->seedOtp($phone, '445566');

        $response = $this->post(route('otp.verify'), [
            'phone' => $phone,
            'code' => '445566',
        ]);

        $response->assertRedirect(route('app'));

        $user = User::where('phone', $normalized)->first();
        $this->assertNotNull($user->organization_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_public_pages_are_accessible(): void
    {
        $this->get(route('privacy'))->assertOk();
        $this->get(route('terms'))->assertOk();
        $this->get(route('contact'))->assertOk();
    }

    public function test_admin_routes_require_super_admin(): void
    {
        $plan = Plan::where('slug', 'basic')->firstOrFail();

        $org = Organization::create([
            'name' => 'Regular Org',
            'language' => 'en',
            'plan_id' => $plan->id,
            'company_type' => 'company',
        ]);

        Subscription::create([
            'organization_id' => $org->id,
            'plan_id' => $plan->id,
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $user = User::create([
            'name' => 'Regular Admin',
            'phone' => '962700000001',
            'role' => User::ROLE_COMPANY_ADMIN,
            'organization_id' => $org->id,
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($user)->get(route('admin.index'))->assertForbidden();
    }

    protected function seedOtp(string $phone, string $code): void
    {
        OtpCode::create([
            'phone' => app(WhatsAppOtp::class)->normalizePhone($phone),
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);
    }
}
