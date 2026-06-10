<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\OrganizationProvisioningService;
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

        if (! $otp->send($phone)) {
            return back()
                ->withInput()
                ->withErrors(['phone' => __('auth.errors.otp_send_failed')]);
        }

        $status = $isExisting ? 'OTP sent to your existing account.' : 'OTP sent to WhatsApp';

        // نعرض صفحة إدخال الكود
        return view('auth.otp-verify', ['phone' => $phone])
               ->with('status', $status);
    }

    // التحقق من الكود
    public function verifyOtp(Request $r, WhatsAppOtp $otp, OrganizationProvisioningService $provisioning)
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
            $provisioning->ensureWorkspace(
                $user,
                $user->name ? ($user->name.' Workspace') : 'Woork customer',
                null,
                'company',
                $user->language ?: app()->getLocale(),
            );
            Auth::login($user->fresh(), true);
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
