<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Support\Facades\App;

class SettingsController extends Controller
{
    public function index() {
        return view('dashboard.settings.index');
    }
    public function language(Request $r) {
        $r->validate(['lang'=>'required|in:en,ar,tr']);
        session(['lang'=>$r->lang]);
        App::setLocale($r->lang);
        return back()->with('ok','Language updated');
    }
    public function organization(Request $r) {
        $r->validate(['company_type'=>'required|in:company,restaurant']);
        $org = $r->user()->organization;
        if ($org) {
            $org->company_type = $r->company_type;
            $org->save();
        }
        return back()->with('ok','Organization updated');
    }
}
