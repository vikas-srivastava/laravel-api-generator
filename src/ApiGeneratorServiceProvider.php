<?php

namespace YourVendorName\ApiGenerator;

use Illuminate\Support\ServiceProvider;

class ApiGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cms_modules.php', 'cms_modules');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\GenerateAPICommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/cms_modules.php' => config_path('cms_modules.php'),
        ], 'config');
    }
}