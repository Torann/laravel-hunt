<?php

namespace LaravelHunt;

use Illuminate\Support\ServiceProvider;

class LaravelHuntServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/hunt.php', 'hunt'
        );
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Hunter::class, function ($app) {
            return new Hunter($app->config->get('hunt'));
        });

        if ($this->app->runningInConsole()) {
            if ($this->isLumen() === false) {
                $this->publishes([
                    __DIR__.'/../config/hunt.php' => config_path('hunt.php'),
                ], 'config');
            }

            $this->commands([
                Console\MapCommand::class,
                Console\FlushCommand::class,
                Console\ImportCommand::class,
                Console\InstallCommand::class,
                Console\UninstallCommand::class,
            ]);
        }
    }

    /**
     * Check if package is running under a Lumen app.
     *
     * @return bool
     */
    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen') === true;
    }
}
