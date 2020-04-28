<?php

namespace CodeGreenCreative\Aweber;

/**
 * The service provider for laravel-aweber
 *
 * @license MIT
 */

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelSamlIdpServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->registerRoutes();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->configure();
        $this->offerPublishing();
        $this->registerServices();
    }

    /**
     * Configure the service provider
     *
     * @return void
     */
    private function configure()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/aweber.php', 'aweber');
    }

    /**
     * Offer publishing for the service provider
     *
     * @return void
     */
    public function offerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/aweber.php' => config_path('aweber.php'),
            ], 'aweber_config');
        }
    }

    /**
     * Register the application bindings.
     *
     * @return void
     */
    private function registerServices()
    {
    }

    /**
     * Register routes for the service provider
     *
     * @return void
     */
    private function registerRoutes()
    {
        Route::name('aweber.')
            ->prefix('aweber')
            ->namespace('CodeGreenCreative\Aweber\Http\Controllers')
            ->middleware('web')->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });
    }
}
