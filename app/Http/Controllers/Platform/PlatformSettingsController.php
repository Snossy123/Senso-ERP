<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;

class PlatformSettingsController extends Controller
{
    public function index()
    {
        $keys = [
            'default_currency',
            'default_trial_days',
            'platform_name',
            'support_email',
            'invoice_prefix',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = PlatformSetting::get($key);
        }

        return view('platform.settings.index', compact('settings', 'keys'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'default_currency' => ['required', 'string', 'size:3'],
            'default_trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'platform_name' => ['required', 'string', 'max:255'],
            'support_email' => ['required', 'email'],
            'invoice_prefix' => ['required', 'string', 'max:20'],
        ]);

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, $value);
        }

        return back()->with('success', __('platform.settings.saved'));
    }
}
