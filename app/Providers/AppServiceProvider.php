<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\App\Services\TenantManager::class, function ($app) {
            return new \App\Services\TenantManager;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Pagination\Paginator::useBootstrapFour();

        Blade::directive('localizedAsset', function ($expression) {
            return "<?php echo asset('assets/' . \\App\\Support\\Locale::cssFolder() . '/' . {$expression}); ?>";
        });
    }
}
