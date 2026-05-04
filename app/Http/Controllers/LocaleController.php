<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LocaleController extends Controller
{
    public function switch(string $locale): RedirectResponse
    {
        if (! in_array($locale, Locale::supportedCodes(), true)) {
            abort(404);
        }

        session(['locale' => $locale]);

        if (Auth::check()) {
            Auth::user()->forceFill(['language' => $locale])->saveQuietly();
        }

        return redirect()->back();
    }
}
