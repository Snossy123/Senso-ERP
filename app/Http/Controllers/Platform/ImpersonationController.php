<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function destroy(Request $request)
    {
        $platformOperatorId = session('platform_operator_id');

        if (! $platformOperatorId) {
            return redirect()->route('dashboard');
        }

        $platformUser = User::withoutGlobalScopes()->find($platformOperatorId);

        session()->forget([
            'platform_operator_id',
            'admin_logged_in_as_tenant',
            'admin_logged_in_as_user',
        ]);

        if (! $platformUser || ! $platformUser->isPlatformOperator()) {
            auth()->logout();

            return redirect()->route('login')
                ->with('error', __('platform.impersonation.session_expired'));
        }

        auth()->login($platformUser);
        $request->session()->regenerate();

        return redirect()->route('platform.dashboard')
            ->with('success', __('platform.impersonation.stopped'));
    }
}
