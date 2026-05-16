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

        $this->ensurePublicStorageLink();
    }

    protected function ensurePublicStorageLink(): void
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (! is_dir($target)) {
            mkdir($target, 0755, true);
        }

        if (! is_dir($target.'/products')) {
            mkdir($target.'/products', 0755, true);
        }

        if (is_link($link) && realpath($link) === realpath($target)) {
            return;
        }

        if (is_link($link) || (file_exists($link) && ! is_dir($link))) {
            @unlink($link);
        }

        if (! file_exists($link)) {
            @symlink($target, $link);
        }
    }
}
