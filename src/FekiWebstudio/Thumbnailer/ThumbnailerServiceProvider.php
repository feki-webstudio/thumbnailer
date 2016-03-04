<?php
namespace FekiWebstudio\Thumbnailer;

use Illuminate\Support\ServiceProvider;

class ThumbnailerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    public $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        // Merge / publish configuration file
        $configFile = __DIR__ . '/../../../resources/config/thumbnailer.php';
        $this->mergeConfigFrom($configFile, 'thumbnailer');

        $this->publishes([
            $configFile => config_path('thumbnailer.php')
        ], 'config');

    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        \App::bind('payment', function()
        {
            return new ThumbFacade();
        });
    }

    /**
     * Get the services provided by the provider
     * @return array
     */
    public function provides()
    {
        return [ 'thumbnailer' ];
    }
}
