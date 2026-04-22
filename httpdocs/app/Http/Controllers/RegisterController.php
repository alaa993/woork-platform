<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\OTP\WhatsAppOtp;
use App\Models\{User, Organization, Subscription, Plan};

class RegisterController extends Controller {
    public function showSignUp(){
        $plans = Plan::all();
        return view('auth.signup', compact('plans'));
    }

    public function submitSignUp(Request $r, WhatsAppOtp $otp){
        $r->validate([
            'name'=>'required|string|max:120',
            'phone'=>'required|string|regex:/^\+?[0-9]{8,15}$/',
            'email'=>'nullable|email:rfc,dns|unique:users,email',
            'org_name'=>'required|string|max:150',
            'company_type'=>'required|in:company,restaurant',
            'language'=>'required|in:ar,en,tr',
            'plan'=>'required',
            'agree'=>'accepted',
        ]);
        $phone = $otp->normalizePhone($r->phone);
        $otp->send($phone);
        session([
            'signup.pending'=>[
                'name'=>$r->name,
                'phone'=>$phone,
                'email'=>$r->email,
                'org_name'=>$r->org_name,
                'company_type'=>$r->company_type,
                'language'=>$r->language,
                'plan'=>$r->plan,
            ]
        ]);
        return view('auth.otp-verify-signup', ['phone'=>$r->phone])
            ->with('status','OTP sent to WhatsApp');
    }

    public function verifyOtpAndCreate(Request $r, WhatsAppOtp $otp){
        $r->validate(['phone'=>'required','code'=>'required']);
        $phone = $otp->normalizePhone($r->phone);
        $pending = session('signup.pending');
        if(!$pending || $pending['phone'] !== $phone)
            return back()->withErrors(['code'=>'Session expired. Please re-submit the form.']);
        if(!$otp->verify($phone,$r->code))
            return back()->withErrors(['code'=>'Invalid or expired OTP']);
        $user = DB::transaction(function() use($pending){
            $user = User::firstOrCreate(
                ['phone'=>$pending['phone']],
                $this->newUserAttributes(
                    $pending['name'],
                    $pending['email'] ?? null,
                    'company_admin'
                )
            );
            if(is_null($user->organization_id)){
                $plan = Plan::where('slug',$pending['plan'])->firstOrFail();
                $org = Organization::create([
                    'name'=>$pending['org_name'],
                    'company_type'=>$pending['company_type'] ?? 'company',
                    'language'=>$pending['language'],
                    'plan_id'=>$plan->id,
                    'owner_user_id'=>$user->id,
                ]);
                Subscription::create([
                    'organization_id'=>$org->id,
                    'plan_id'=>$plan->id,
                    'status'=>'trial',
                    'trial_ends_at'=>now()->addDays(14),
                    'current_period_end'=>now()->addDays(14),
                ]);
                $user->organization_id=$org->id;
                $user->name=$user->name?:$pending['name'];
                $user->email=$user->email?:$pending['email'];
                $user->save();
            }
            return $user;
        });
        session()->forget('signup.pending');
        Auth::login($user,true);
        return redirect()->route('app')->with('ok','Welcome to Woork!');
    }

    public function show() {
        $phone = session('verified_phone');
        if (!$phone) {
            return redirect()->route('login');
        }
        return view('auth.register', compact('phone'));
    }

    public function store(Request $r) {
        $phone = session('verified_phone');
        if (!$phone) {
            return redirect()->route('login');
        }

        $data = $r->validate([
            'name'=>'required|string|max:120',
            'email'=>'nullable|email:rfc,dns|unique:users,email',
        ]);

        $user = User::firstOrCreate(
            ['phone'=>$phone],
            $this->newUserAttributes(
                $data['name'],
                $data['email'] ?? null,
                User::ROLE_COMPANY_ADMIN
            )
        );

        $user->fill([
            'name'=>$data['name'],
            'email'=>$data['email'] ?? $user->email,
        ]);
        if (empty($user->password)) {
            $user->password = bcrypt(Str::random(24));
        }
        $user->save();

        session()->forget('verified_phone');

        Auth::login($user, true);
        return redirect()->route('app')->with('ok','Account ready');
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
