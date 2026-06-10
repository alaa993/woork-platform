<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\OTP\WhatsAppOtp;
use App\Services\OrganizationProvisioningService;
use App\Models\{User, Plan};

class RegisterController extends Controller
{
    public function showSignUp()
    {
        $plans = Plan::query()->where('is_active', true)->orderBy('price_monthly')->get();

        return view('auth.signup', compact('plans'));
    }

    public function submitSignUp(Request $r, WhatsAppOtp $otp)
    {
        $plans = Plan::query()->where('is_active', true)->pluck('slug')->all();
        abort_if(empty($plans), 503, 'No subscription plans are configured yet.');

        $r->validate([
            'name' => 'required|string|max:120',
            'phone' => 'required|string|regex:/^\+?[0-9]{8,15}$/',
            'email' => 'nullable|email|unique:users,email',
            'org_name' => 'required|string|max:150',
            'company_type' => 'required|in:company,restaurant',
            'language' => 'required|in:ar,en,tr',
            'plan' => ['required', Rule::in($plans)],
            'agree' => 'accepted',
        ]);

        $phone = $otp->normalizePhone($r->phone);
        if (! $otp->send($phone)) {
            return back()
                ->withInput()
                ->withErrors(['phone' => __('auth.errors.otp_send_failed')]);
        }
        session([
            'signup.pending' => [
                'name' => $r->name,
                'phone' => $phone,
                'email' => $r->email,
                'org_name' => $r->org_name,
                'company_type' => $r->company_type,
                'language' => $r->language,
                'plan' => $r->plan,
            ],
        ]);

        return view('auth.otp-verify-signup', ['phone' => $r->phone])
            ->with('status', 'OTP sent to WhatsApp');
    }

    public function verifyOtpAndCreate(Request $r, WhatsAppOtp $otp, OrganizationProvisioningService $provisioning)
    {
        $r->validate(['phone' => 'required', 'code' => 'required']);
        $phone = $otp->normalizePhone($r->phone);
        $pending = session('signup.pending');
        if (! $pending || $pending['phone'] !== $phone) {
            return back()->withErrors(['code' => 'Session expired. Please re-submit the form.']);
        }
        if (! $otp->verify($phone, $r->code)) {
            return back()->withErrors(['code' => 'Invalid or expired OTP']);
        }

        $user = DB::transaction(function () use ($pending, $provisioning) {
            $user = User::firstOrCreate(
                ['phone' => $pending['phone']],
                $this->newUserAttributes(
                    $pending['name'],
                    $pending['email'] ?? null,
                    User::ROLE_COMPANY_ADMIN
                )
            );

            $user->fill([
                'name' => $user->name ?: $pending['name'],
                'email' => $user->email ?: $pending['email'],
            ]);
            if (empty($user->password)) {
                $user->password = bcrypt(Str::random(24));
            }
            $user->save();

            return $provisioning->ensureWorkspace(
                $user,
                $pending['org_name'],
                $pending['plan'],
                $pending['company_type'] ?? 'company',
                $pending['language'],
            );
        });

        session()->forget('signup.pending');
        Auth::login($user, true);

        return redirect()->route('app')->with('ok', 'Welcome to Woork!');
    }

    public function show()
    {
        $phone = session('verified_phone');
        if (! $phone) {
            return redirect()->route('login');
        }

        return view('auth.register', compact('phone'));
    }

    public function store(Request $r, OrganizationProvisioningService $provisioning)
    {
        $phone = session('verified_phone');
        if (! $phone) {
            return redirect()->route('login');
        }

        $data = $r->validate([
            'name' => 'required|string|max:120',
            'email' => 'nullable|email|unique:users,email',
        ]);

        $user = User::firstOrCreate(
            ['phone' => $phone],
            $this->newUserAttributes(
                $data['name'],
                $data['email'] ?? null,
                User::ROLE_COMPANY_ADMIN
            )
        );

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'] ?? $user->email,
        ]);
        if (empty($user->password)) {
            $user->password = bcrypt(Str::random(24));
        }
        $user->save();

        $provisioning->ensureWorkspace(
            $user,
            $data['name'].' Workspace',
            null,
            'company',
            app()->getLocale(),
        );

        session()->forget('verified_phone');

        Auth::login($user->fresh(), true);

        return redirect()->route('app')->with('ok', 'Account ready');
    }

    protected function newUserAttributes(string $name, ?string $email, string $role): array
    {
        $attributes = [
            'name' => $name,
            'role' => $role,
            'password' => bcrypt(Str::random(24)),
        ];

        if (! empty($email)) {
            $attributes['email'] = $email;
        }

        return $attributes;
    }
}
