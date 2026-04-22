<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    AuthController, DashboardController, RoomsController, CamerasController,
    EmployeesController, AlertsController, PoliciesController, SubscriptionController,
    SettingsController, StripeWebhookController, AdminController, BillingController, ExportController,
    RegisterController, ProfileController, AgentDevicesController, CameraHealthController, AgentReleasesController,
    ReportsController, OnboardingController, LaunchReadinessController
};


// الصفحة العامة + الدخول
Route::middleware([App\Http\Middleware\SetLocale::class])->group(function () {
    Route::get('/', function () {
        if (Auth::check()) {
            return redirect()->route('app');
        }
        return view('public.landing');
    })->name('landing');

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

    // OTP (صحّحنا ترتيب/سلسلة الاستدعاءات)
    Route::post('/otp/request', [AuthController::class, 'requestOtp'])
        ->middleware('throttle:5,1')
        ->name('otp.request');

    Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:5,1')
        ->name('otp.verify');
	
	

Route::get('/register', [RegisterController::class, 'show'])->name('register.show');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
	
	Route::get('/signup', [RegisterController::class,'showSignUp'])->name('signup');
Route::post('/signup', [RegisterController::class,'submitSignUp'])->name('signup.submit');
Route::post('/signup/verify', [RegisterController::class,'verifyOtpAndCreate'])->name('signup.verify');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
	
	
});

// مناطق التطبيق المحمية
Route::middleware([
    'auth',
    App\Http\Middleware\TenantScope::class,
    App\Http\Middleware\EnforceSubscription::class
])->group(function () {

    Route::get('/app', [DashboardController::class, 'index'])->name('app');

    Route::prefix('app')->group(function () {
        Route::resource('rooms', RoomsController::class);
        Route::resource('cameras', CamerasController::class)->except(['show']);
        Route::get('camera-health', [CameraHealthController::class, 'index'])->name('camera-health.index');
        Route::get('camera-health/{camera}', [CameraHealthController::class, 'show'])->name('camera-health.show');
        Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
        Route::get('readiness', [LaunchReadinessController::class, 'index'])->name('readiness.index');
        Route::get('getting-started', [OnboardingController::class, 'index'])->name('onboarding.index');
        Route::get('agent-releases', [AgentReleasesController::class, 'index'])->name('agent-releases.index');
        Route::resource('agent-devices', AgentDevicesController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('employees', EmployeesController::class)->except(['show']);
        Route::get('agent-devices/{agentDevice}/install', [AgentDevicesController::class, 'install'])->name('agent-devices.install');
        Route::get('agent-devices/{agentDevice}/validation', [AgentDevicesController::class, 'validation'])->name('agent-devices.validation');
        Route::post('agent-devices/{agentDevice}/rotate-token', [AgentDevicesController::class, 'rotatePairingToken'])->name('agent-devices.rotate-token');

        Route::get('alerts', [AlertsController::class, 'index'])->name('alerts.index');
        Route::post('alerts/{alert}/resolve', [AlertsController::class, 'resolve'])->name('alerts.resolve');

        Route::get('policies', [PoliciesController::class, 'index'])->name('policies.index');
        Route::put('policies', [PoliciesController::class, 'update'])->name('policies.update');

        Route::get('subscription', [SubscriptionController::class, 'index'])->name('subscription.index');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings/language', [SettingsController::class, 'language'])->name('settings.language');
        Route::post('settings/organization', [SettingsController::class, 'organization'])->name('settings.organization');
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        // التصدير
        Route::get('export/daily.csv', [ExportController::class, 'dailyCsv'])->name('export.daily.csv');
        // (لو عندك PDF)
        // Route::get('export/daily.pdf', [ExportController::class, 'dailyPdf'])->name('export.daily.pdf');

        // الفوترة
        Route::post('billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
        Route::post('billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
    });
});

// لوحة السوبر أدمن (إن لزم لاحقًا أضف middleware مناسب)
Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
Route::get('/admin/agent-releases/create', [AgentReleasesController::class, 'create'])->name('admin.agent-releases.create');
Route::post('/admin/agent-releases', [AgentReleasesController::class, 'store'])->name('admin.agent-releases.store');

// Webhook Stripe
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');
