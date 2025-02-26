<?php

declare(strict_types=1);

namespace GrokPHP\Laravel;

use Illuminate\Support\ServiceProvider;
use GrokPHP\Client\GrokClient;
use GrokPHP\Console\InstallCommand;
use GrokPHP\Enums\Model;

class GrokServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/grok.php', 'grok');

        $this->app->singleton(GrokClient::class, function ($app) {
            $client = new GrokClient(config('grok.api_key'));

            if ($model = Model::tryFrom(config('grok.default_model'))) {
                $client->model($model);
            }

            return $client;
        });

        $this->app->alias(GrokClient::class, 'grok');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../../config/grok.php' => config_path('grok.php'),
            ], 'grok');
        }
    }
}
