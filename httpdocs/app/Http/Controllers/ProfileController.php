<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        return view('dashboard.profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'nullable|email:rfc,dns|unique:users,email,' . $user->id,
            'language' => 'required|in:ar,en,tr',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'language' => $data['language'],
        ]);

        session(['lang' => $data['language']]);
        App::setLocale($data['language']);

        return back()->with('ok', __('dashboard.profile_updated'));
    }
}
