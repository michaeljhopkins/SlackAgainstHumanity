<?php namespace Hopkins\SlackAgainstHumanity\Providers;

use Illuminate\Support\ServiceProvider;
use Maknz\Slack\Client;
use GuzzleHttp\Client as Guzzle;

class SahServiceProvider extends ServiceProvider
{

    public function boot()
    {
        include __DIR__ . '/../Http/routes.php';
    }

    public function register()
    {

    }
}
