<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RenokiCo\PhpK8s\Kinds\K8sPod;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        K8sPod::macro('getLabel', function (string $name, $default = null) {
            return $this->getLabels()[$name] ?? $default;
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
