<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    // دمج إعداد Vite + مشاركة $menu في boot واحدة
    public function boot(Vite $vite): void
    {
        // مسار مجلد البناء الخاص بـ Vite
        $vite->useBuildDirectory('build');

        // مشاركة قائمة $menu لكل الـ views عند تسجيل الدخول
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $role = Auth::user()->role ?? 'company_admin';

                $menu = match ($role) {
                    'super_admin' => [
                        ['icon'=>'dashboard','label'=>'dashboard.dashboard','route'=>'app'],
                        ['icon'=>'admin','label'=>'dashboard.users','route'=>'admin.users'],
                        ['icon'=>'download','label'=>'dashboard.agent_releases','route'=>'agent-releases.index'],
                        ['icon'=>'export','label'=>'dashboard.export_csv','route'=>'export.daily.csv'],
                        ['icon'=>'settings','label'=>'dashboard.settings','route'=>'settings.index'],
                    ],
                    'employee' => [
                        ['icon'=>'dashboard','label'=>'dashboard.my_dashboard','route'=>'app'],
                        ['icon'=>'bell','label'=>'dashboard.alerts','route'=>'alerts.index'],
                        ['icon'=>'settings','label'=>'dashboard.settings','route'=>'settings.index'],
                    ],
                    default => [
                        ['icon'=>'dashboard','label'=>'dashboard.dashboard','route'=>'app'],
                        ['icon'=>'device','label'=>'dashboard.getting_started','route'=>'onboarding.index'],
                        ['icon'=>'pulse','label'=>'dashboard.launch_readiness','route'=>'readiness.index'],
                        ['icon'=>'rooms','label'=>'dashboard.rooms','route'=>'rooms.index'],
                        ['icon'=>'camera','label'=>'dashboard.cameras','route'=>'cameras.index'],
                        ['icon'=>'export','label'=>'dashboard.reports','route'=>'reports.index'],
                        ['icon'=>'pulse','label'=>'dashboard.camera_health','route'=>'camera-health.index'],
                        ['icon'=>'device','label'=>'dashboard.agent_devices','route'=>'agent-devices.index'],
                        ['icon'=>'download','label'=>'dashboard.agent_releases','route'=>'agent-releases.index'],
                        ['icon'=>'users','label'=>'dashboard.employees','route'=>'employees.index'],
                        ['icon'=>'bell','label'=>'dashboard.alerts','route'=>'alerts.index'],
                        ['icon'=>'policy','label'=>'dashboard.policies','route'=>'policies.index'],
                        ['icon'=>'user','label'=>'dashboard.profile','route'=>'profile.show'],
                        ['icon'=>'billing','label'=>'dashboard.subscription','route'=>'subscription.index'],
                        ['icon'=>'export','label'=>'dashboard.export_csv','route'=>'export.daily.csv'],
                        ['icon'=>'settings','label'=>'dashboard.settings','route'=>'settings.index'],
                    ],
                };

                // (اختياري) فلترة العناصر ذات المسارات غير المعرّفة لتجنب الأخطاء
                $menu = array_values(array_filter($menu, fn($i) => Route::has($i['route'])));

                $view->with('menu', $menu);
            } else {
                // صفحات الضيف: امنع undefined variable
                $view->with('menu', []);
            }
        });
    }
}
