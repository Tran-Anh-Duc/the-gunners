<?php

namespace App\Providers;

use App\Models\Action;
use App\Models\Module;
use App\Observers\ActionObserver;
use App\Observers\ModuleObserver;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->guardDestructiveDatabaseCommands();

        Action::observe(ActionObserver::class);
        Module::observe(ModuleObserver::class);
    }

    protected function guardDestructiveDatabaseCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        if (filter_var(env('ALLOW_DESTRUCTIVE_DB_COMMANDS', false), FILTER_VALIDATE_BOOL)) {
            return;
        }

        $command = $_SERVER['argv'][1] ?? null;

        if (! in_array($command, ['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:wipe'], true)) {
            return;
        }

        $defaultConnection = config('database.default');
        $dbHost = (string) config("database.connections.{$defaultConnection}.host", '');

        if (! str_contains($dbHost, 'aivencloud.com')) {
            return;
        }

        throw new RuntimeException(
            'Refusing to run destructive database commands against Aiven. '
            .'Set ALLOW_DESTRUCTIVE_DB_COMMANDS=true only if you intentionally want to bypass this guard.'
        );
    }
}
