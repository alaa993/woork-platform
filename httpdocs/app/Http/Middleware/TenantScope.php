<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class TenantScope
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // ✅ السوبر أدمن يتجاوز أي تقييد خاص بالمنظمة
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return $next($request);
            }

            // ✅ الباقون (الإداريين والموظفين) يطبق عليهم tenant scope
            if ($user->organization_id) {
                $request->attributes->set('org_id', $user->organization_id);
            } else {
                // لو ما عنده organization_id نمنعه من الوصول (إجراء وقائي)
                abort(403, 'Organization not assigned');
            }
        }

        return $next($request);
    }
}