<?php namespace Hopkins\SlackAgainstHumanity\Providers;

use Illuminate\Support\ServiceProvider;

class SahServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../Database/migrations/' => database_path('migrations')
        ], 'migrations');

        $this->publishes([
            __DIR__.'/../Database/seeds/' => database_path('seeds')
        ], 'seeds');
    }

    public function register()
    {

    }
}
