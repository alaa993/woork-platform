<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{User, Organization, Plan, Subscription};
use App\Services\OTP\WhatsAppOtp;

class AuthController extends Controller
{
    // صفحة إدخال الهاتف
    public function showLogin()
    {
        return view('auth.login');
    }

    // طلب إرسال OTP
    public function requestOtp(Request $r, WhatsAppOtp $otp)
    {
        $r->validate([
            'phone' => ['required','string','regex:/^\+?[0-9]{8,15}$/'],
        ]);

        $phone = $otp->normalizePhone($r->phone);
        $isExisting = User::where('phone', $phone)->exists();

        // نرسل الكود (لا نعتمد على قيمة الإرجاع)
        $otp->send($phone);

        $status = $isExisting ? 'OTP sent to your existing account.' : 'OTP sent to WhatsApp';

        // نعرض صفحة إدخال الكود
        return view('auth.otp-verify', ['phone' => $phone])
               ->with('status', $status);
    }

    // التحقق من الكود
    public function verifyOtp(Request $r, WhatsAppOtp $otp)
    {
        $r->validate([
            'phone' => ['required','string'],
            'code'  => ['required','string'],
        ]);

        $phone = $otp->normalizePhone($r->phone);

        if (! $otp->verify($phone, $r->code)) {
            return back()->withErrors(['code' => 'Invalid or expired OTP']);
        }

        // لو المستخدم موجود برقم الهاتف → سجّل دخوله
        if ($user = User::where('phone', $phone)->first()) {
            if (!$user->organization_id) {
                $plan = Plan::where('slug', 'starter')->first() ?? Plan::first();
                $trialEndsAt = Subscription::trialEndsAtFor($plan);
                $org = Organization::create([
                    'name' => $user->name ?: 'Woork customer',
                    'language' => $user->language ?: app()->getLocale(),
                    'plan_id' => $plan?->id,
                    'owner_user_id' => $user->id,
                ]);
                Subscription::create([
                    'organization_id' => $org->id,
                    'plan_id' => $plan?->id,
                    'status' => 'trial',
                    'trial_ends_at' => $trialEndsAt,
                    'current_period_end' => $trialEndsAt,
                ]);
                $user->organization_id = $org->id;
                $user->save();
            }
            Auth::login($user, true);
            return redirect()->route('app');
        }

        // جديد: خزّن الهاتف مؤقتًا واذهب لصفحة إكمال التسجيل
        session(['verified_phone' => $phone]);

        return redirect()->route('register.show');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('landing');
    }
}
