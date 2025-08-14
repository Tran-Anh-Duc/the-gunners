<?php

namespace App\Providers;

use App\Models\Action;
use App\Models\Module;
use App\Observers\ActionObserver;
use App\Observers\ModuleObserver;
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
        Action::observe(ActionObserver::class);
        Module::observe(ModuleObserver::class);
    }
}
