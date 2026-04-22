<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /** اللغات المسموح بها */
    private array $available = ['en', 'ar', 'tr'];

    public function handle(Request $request, Closure $next)
    {
        // 1️⃣ قراءة اللغة من: ?lang أو cookie أو session
        $lang = $request->query('lang')
            ?? $request->cookie('woork_lang')
            ?? session('lang')
            ?? config('app.locale', 'en');

        // 2️⃣ لو اللغة غير مدعومة → استخدم الإنجليزية
        if (! in_array($lang, $this->available, true)) {
            $lang = 'en';
        }

        // 3️⃣ ضبط اللغة للتطبيق
        App::setLocale($lang);
        session(['lang' => $lang]);

        // 4️⃣ المتابعة مع إضافة cookie لتخزين الاختيار لمدة سنة
        $response = $next($request);
        if ($request->has('lang')) {
            $response->headers->setCookie(cookie('woork_lang', $lang, 60 * 24 * 365));
        }

        return $response;
    }
}