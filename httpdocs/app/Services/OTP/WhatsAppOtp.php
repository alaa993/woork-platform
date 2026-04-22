<?php

namespace App\Services\OTP;

use App\Models\OtpCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppOtp
{
    public function send(string $rawPhone): bool
    {
        $phone = $this->normalizePhone($rawPhone);

        // يولّد كود 6 أرقام
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // احفظ الكود مع الصلاحية (10 دقائق)
        OtpCode::create([
            'phone'      => $phone,
            'code'       => $code,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $text = "Your Woork OTP is: {$code} (valid 10 min)";
        return $this->dispatch($phone, $text);
    }

    public function verify(string $rawPhone, string $code): bool
    {
        $phone = $this->normalizePhone($rawPhone);

        $otp = OtpCode::where('phone', $phone)
            ->where('code', $code)
            ->whereNull('consumed_at')
            ->first();

        if (!$otp || Carbon::now()->greaterThan($otp->expires_at)) {
            return false;
        }

        $otp->consumed_at = Carbon::now();
        $otp->save();
        return true;
    }

    /**
     * إرسال عبر StandingTech (يطابق المثال في لوحتهم)
     */
   // app/Services/OTP/WhatsAppOtp.php (المقتطفات المهمة فقط)

protected function dispatch(string $phone, string $text): bool
{
    $driver = config('services.whatsapp.driver','log');

    if ($driver === 'standingtech') {
        $url   = rtrim(config('services.whatsapp.standingtech.url'), '/');
        $token = config('services.whatsapp.standingtech.token');
        $senderId = config('services.whatsapp.standingtech.sender_id');
        $type  = config('services.whatsapp.standingtech.type','whatsapp');
        $lang  = config('services.whatsapp.standingtech.lang','en');

        // payload تمامًا كما في الدوك
        $payload = [
            'recipient' => $phone,     // أرقام فقط مع كود الدولة (بدون +/00)
            'sender_id' => $senderId,
            'type'      => $type,
            'message'   => $text,
            'lang'      => $lang,
        ];

        $resp = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])->post($url, $payload);

        if ($resp->successful()) {
            return true;
        }

        \Log::error('StandingTech send failed', [
            'status' => $resp->status(),
            'body'   => $resp->body(),
            'payload'=> $payload,
        ]);

        return false;
    }

    // باقي الدرايفرز
    \Log::info("[WA-OTP-{$driver}] to={$phone} text={$text}");
    return true;
}

    public function normalizePhone(string $raw): string
{
    // احذف كل ما ليس رقمًا
    $digits = preg_replace('/\D+/', '', $raw);

    // لو يبدأ بـ 00 حوّله لصيغة أرقام فقط (مثلاً 00962... => 962...)
    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }
    // لو يبدأ بـ 0 (رقم محلي) وأنت تعرف كود الدولة الافتراضي.. أضفه:
    // مثال: الأردن 962، العراق 964، السعودية 966 ... اختر ما يناسبك
    if (str_starts_with($digits, '0') && env('OTP_DEFAULT_CC')) {
        $digits = rtrim(env('OTP_DEFAULT_CC')) . ltrim($digits, '0');
    }

    return $digits; // أرقام فقط مع كود الدولة، بدون +/00
}
}
