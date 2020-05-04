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

class AweberServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // $this->registerRoutes();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // $this->configure();
        $this->offerPublishing();
        // $this->registerServices();
        $this->registerFacades();
        // $this->registerCommands();
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
        $app = $this->app;

        if (version_compare($app::VERSION, '5.0') < 0) {
            \Config::package('codegreencreative/laravel-aweber', 'laravel-aweber');
        } elseif ($this->app->runningInConsole()) {
            $this->publishes(array(
                __DIR__ . '/../config/aweber.php' => config_path('aweber.php'),
            ), 'aweber_config');
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

    public function registerFacades()
    {
        $this->app->singleton('aweber', function ($app) {
            return new Aweber;
        });
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
    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(array(
                '\CodeGreenCreative\Aweber\Console\AweberAuthorize',
            ));
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('laravel-aweber');
    }
}
