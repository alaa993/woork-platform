<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Subscription; // ← مهم

class EnforceSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user) {
            // سيوقفه middleware 'auth' لاحقاً
            return $next($request);
        }

        // السماح للسوبر أدمن وصفحات الفوترة/الويبهوك
        if (
            ($user->role ?? null) === 'super_admin' ||
            $request->routeIs('subscription.*', 'billing.*', 'stripe.webhook')
        ) {
            return $next($request);
        }

        // تحقق حالة الاشتراك من جدول subscriptions
        $orgId = $user->organization_id;
        $sub = $orgId
            ? Subscription::where('organization_id', $orgId)->latest('id')->first()
            : null;

        $active = $sub?->isCurrentlyActive() ?? false;

        if ($active) {
            return $next($request);
        }

        // API
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'error' => 'Subscription required'], 402);
        }

        // Web
        return redirect()
            ->route('subscription.index')
            ->with('error', __('Your subscription is inactive. Please renew to continue.'));
    }
}
