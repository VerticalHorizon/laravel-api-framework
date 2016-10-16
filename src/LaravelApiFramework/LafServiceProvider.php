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
        $this->publishes([realpath(__DIR__.'/../../config/api.php') => config_path('api.php')]);
        $this->publishes([realpath(__DIR__.'/../../config/rules.php') => config_path('rules.php')]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        include __DIR__.'/routes.php';

        $this->app->singleton('Karellens\LAF\QueryMap', function ($app) {
            return new \Karellens\LAF\QueryMap;
        });
        $this->app->singleton('Karellens\LAF\Rules', function ($app) {
            return new \Karellens\LAF\Rules;
        });
//        $this->app->make('Karellens\LAF\Http\Controllers\ApiController');
    }
}
