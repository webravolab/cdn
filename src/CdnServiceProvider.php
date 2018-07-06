<?php

namespace Webravolab\Cdn;

use Illuminate\Support\ServiceProvider;
// use Webravolab\Cdn\Contracts\CdnHelperInterface;
// use Webravolab\Cdn\CdnHelper;

class CdnServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/webravo_cdn.php' => config_path('webravo_cdn.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Include custom routes
        include __DIR__.'/routes/web.php';

        // Include custom controller
        $this->app->make('Webravolab\Cdn\CdnController');

        // Interface Bindings
        $this->app->bind(
            'Webravolab\Cdn\Contracts\CdnHelperInterface',
            'Webravolab\Cdn\CdnHelper'
        );
        $this->app->bind(
            'Webravolab\Cdn\Contracts\ProviderFactoryInterface',
            'Webravolab\Cdn\ProviderFactory'
        );

        // Facade Bindings

        // Register 'CdnFacade' instance container to our CdnFacade object
        $this->app->singleton('cdn', function ($app) {
            return $app->make('Webravolab\Cdn\Cdn');
        });

        // Shortcut so developers don't need to add an Alias in app/config/app.php
        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Cdn', 'Webravolab\Cdn\Facades\CdnFacade');
        });
    }
}
