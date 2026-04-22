<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    // إنشاء عملية الدفع (checkout)
    public function checkout(Request $r)
    {
        $user = Auth::user();

        // ✅ إنشاء Stripe Customer إن لم يكن موجودًا
        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        $price = $r->input('price', config('services.stripe.price_basic_monthly'));

        return $user->checkout($price, [
            'success_url' => url('/app/subscription?success=1'),
            'cancel_url'  => url('/app/subscription?canceled=1'),
        ]);
    }

    // بوابة الفواتير (Billing Portal)
    public function portal()
    {
        $user = Auth::user();

        // ✅ إنشاء Stripe Customer إن لم يكن موجودًا
        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        // ✅ الآن التحويل إلى بوابة Stripe Billing
        return $user->redirectToBillingPortal(url('/app/subscription'));
    }
}