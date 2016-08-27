<?php

namespace Karellens\LAF;

use Illuminate\Support\ServiceProvider;

class LafServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([realpath(__DIR__.'/../config/api.php') => config_path('api.php')]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        include __DIR__.'/routes.php';
        $this->app->make('Karellens\LAF\Http\Controllers\ApiController');
    }
}
