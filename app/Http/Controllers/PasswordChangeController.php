<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PasswordChangeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show()
    {
        $user = $this->staffUser();

        if (! $user || ! $user->mustChangePassword()) {
            return redirect()->to($user?->applicationHomeRoute() ?? route('dashboard'));
        }

        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $user = $this->staffUser();

        if (! $user || ! $user->mustChangePassword()) {
            abort(Response::HTTP_FORBIDDEN, __('auth_pages.change_password.not_required'));
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = $validated['password'];
        $user->must_change_password = false;
        $user->password_changed_at = now();
        $user->save();

        return redirect()
            ->to($user->applicationHomeRoute())
            ->with('success', __('auth_pages.password_changed'));
    }

    protected function staffUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
