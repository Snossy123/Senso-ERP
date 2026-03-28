<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function __invoke()
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $appSettings = [
            'app_name' => config('app.name'),
            'app_currency' => config('app.currency'),
            'app_currency_symbol' => config('app.currency_symbol', '$'),
            'app_tax_rate' => config('app.tax_rate', 0),
            'app_address' => config('app.address', ''),
            'app_phone' => config('app.phone', ''),
            'app_email' => config('app.email', ''),
        ];

        return view('admin.settings.index', compact('appSettings'));
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_currency' => 'required|string|max:10',
            'app_currency_symbol' => 'required|string|max:5',
            'app_tax_rate' => 'required|numeric|min:0|max:100',
        ]);

        $this->updateEnv([
            'APP_NAME' => $request->app_name,
            'APP_CURRENCY' => $request->app_currency,
            'APP_CURRENCY_SYMBOL' => $request->app_currency_symbol,
            'APP_TAX_RATE' => $request->app_tax_rate,
            'APP_ADDRESS' => $request->app_address ?? '',
            'APP_PHONE' => $request->app_phone ?? '',
            'APP_EMAIL' => $request->app_email ?? '',
        ]);

        return back()->with('success', 'Settings saved and cached cleared.');
    }

    protected function updateEnv($data)
    {
        $envPath = base_path('.env');
        $envContent = File::exists($envPath) ? File::get($envPath) : '';

        foreach ($data as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);

        Artisan::call('config:clear');
        Artisan::call('cache:clear');
    }
}
