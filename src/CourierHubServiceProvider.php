<?php

namespace CourierHub;

use CourierHub\Console\CourierStatusCommand;
use Illuminate\Support\ServiceProvider;

class CourierHubServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/courierhub.php',
            'courierhub'
        );

        $this->app->singleton(CourierManager::class, function ($app) {
            return new CourierManager($app);
        });

        $this->app->alias(CourierManager::class, 'courier');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->registerRoutes();
        $this->registerCommands();
    }

    /**
     * Publish configuration file.
     */
    protected function publishConfig(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/courierhub.php' => config_path('courierhub.php'),
            ], 'courierhub-config');
        }
    }

    /**
     * Publish migration files.
     */
    protected function publishMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'courierhub-migrations');
        }
    }

    /**
     * Register webhook routes.
     */
    protected function registerRoutes(): void
    {
        $webhookPath = config('courierhub.webhook.path', 'webhooks/courier');
        $middleware = config('courierhub.webhook.middleware', []);

        $this->app['router']
            ->post("{$webhookPath}/{provider}", [
                \CourierHub\Http\Controllers\WebhookController::class,
                'handle',
            ])
            ->middleware($middleware)
            ->name('courierhub.webhook');
    }

    /**
     * Register artisan commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CourierStatusCommand::class,
            ]);
        }
    }
}
