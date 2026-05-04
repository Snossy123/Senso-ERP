<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $codes = Locale::supportedCodes();
        $resolved = null;

        if ($request->filled('lang')) {
            $lang = $request->string('lang')->toString();
            if (in_array($lang, $codes, true)) {
                session(['locale' => $lang]);
                if (Auth::check()) {
                    Auth::user()->forceFill(['language' => $lang])->saveQuietly();
                }
                $resolved = $lang;
            }
        }

        if ($resolved === null && session()->has('locale')) {
            $sessionLocale = session('locale');
            if (is_string($sessionLocale) && in_array($sessionLocale, $codes, true)) {
                $resolved = $sessionLocale;
            }
        }

        if ($resolved === null && Auth::check()) {
            $userLang = Auth::user()->language;
            if (is_string($userLang) && in_array($userLang, $codes, true)) {
                $resolved = $userLang;
            }
        }

        if ($resolved === null) {
            $tenantLang = config('tenant.localization.language');
            if (is_string($tenantLang) && in_array($tenantLang, $codes, true)) {
                $resolved = $tenantLang;
            }
        }

        if ($resolved === null) {
            $resolved = (string) config('app.locale', 'en');
        }

        if (! in_array($resolved, $codes, true)) {
            $resolved = 'en';
        }

        app()->setLocale($resolved);

        View::share('locale', $resolved);
        View::share('dir', Locale::dir());
        View::share('isRtl', Locale::isRtl());

        return $next($request);
    }
}
