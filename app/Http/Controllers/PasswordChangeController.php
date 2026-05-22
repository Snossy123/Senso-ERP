<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordChangeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show()
    {
        $user = Auth::user();

        if (! $user || ! $user->mustChangePassword()) {
            return redirect()->route('dashboard');
        }

        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = $validated['password'];
        $user->must_change_password = false;
        $user->password_changed_at = now();
        $user->save();

        return redirect()
            ->route('dashboard')
            ->with('success', __('auth_pages.password_changed'));
    }
}
