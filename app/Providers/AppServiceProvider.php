<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        \App\Models\Action::observe(\App\Observers\ActionObserver::class);
        \App\Models\Module::observe(\App\Observers\ModuleObserver::class);
    }
}
