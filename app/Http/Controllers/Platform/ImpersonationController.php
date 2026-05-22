<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function destroy(Request $request)
    {
        if (! session()->has('platform_operator_id')) {
            return redirect()->route('dashboard');
        }

        session()->forget([
            'platform_operator_id',
            'admin_logged_in_as_tenant',
            'admin_logged_in_as_user',
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', __('platform.impersonation.stopped'));
    }
}
