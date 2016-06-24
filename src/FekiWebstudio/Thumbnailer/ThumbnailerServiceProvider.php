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

        // Create output path if it doesn't exist yet
        if (! is_dir(public_path(config('thumbnailer.output_path')))) {
            mkdir(public_path(config('thumbnailer.output_path'), 0777));
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton('thumb', function ($app) {
            return new ThumberManager($app);
        });

        $this->app->bind('thumb', function()
        {
            return new ThumbnailManager();
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
