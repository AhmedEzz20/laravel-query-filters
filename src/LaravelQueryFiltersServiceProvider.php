<?php

namespace Astro\LaravelQueryFilters;

use Illuminate\Support\ServiceProvider;
use Astro\LaravelQueryFilters\Commands\MakeFilterCommand;

class LaravelQueryFiltersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeFilterCommand::class,
            ]);
        }
    }
}