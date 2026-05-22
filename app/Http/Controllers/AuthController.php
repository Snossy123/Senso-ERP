<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->intended($this->homeFor(Auth::user()));
        }

        return view('signin');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials['is_active'] = true;

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->tenant_id) {
                $tenant = Tenant::find($user->tenant_id);
                if (! $tenant || ! $tenant->allowsApplicationAccess()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()
                        ->withErrors(['email' => __('messages.errors.tenant_access_unavailable')])
                        ->onlyInput('email');
                }
            }

            Activity::logLogin($user);

            if ($user->mustChangePassword()) {
                return redirect()->route('password.change');
            }

            return redirect()->intended($this->homeFor($user));
        }

        return back()->withErrors(['email' => __('auth_pages.signin.invalid_credentials')])->onlyInput('email');
    }

    protected function homeFor($user): string
    {
        return $user->isPlatformOperator()
            ? route('platform.dashboard')
            : route('dashboard');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            Activity::logLogout($user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
