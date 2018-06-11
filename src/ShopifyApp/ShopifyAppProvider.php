<?php

namespace OhMyBrew\ShopifyApp;

use Illuminate\Support\ServiceProvider;

class ShopifyAppProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Routes
        $this->loadRoutesFrom(__DIR__.'/resources/routes.php');

        // Views
        $this->loadViewsFrom(__DIR__.'/resources/views', 'shopify-app');

        // Config publish
        $this->publishes([
            __DIR__.'/resources/config/shopify-app.php' => config_path('shopify-app.php'),
        ], 'config');

        // Database migrations
        $this->publishes([
            __DIR__.'/resources/database/migrations' => database_path('migrations'),
        ], 'migrations');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge options with published config
        $this->mergeConfigFrom(__DIR__.'/resources/config/shopify-app.php', 'shopify-app');

        // ShopifyApp facade
        $this->app->bind('shopifyapp', function ($app) {
            return new ShopifyApp($app);
        });

        // Commands
        $this->commands([
            \OhMyBrew\ShopifyApp\Console\WebhookJobMakeCommand::class,
        ]);
    }
}
