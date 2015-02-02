<?php namespace Hopkins\SlackAgainstHumanity\Providers;

use Illuminate\Support\ServiceProvider;
use Maknz\Slack\Client;
use GuzzleHttp\Client as Guzzle;

class SahServiceProvider extends ServiceProvider
{

    public function boot()
    {
        include __DIR__.'/../Http/routes.php';
        $this->publishes([
            __DIR__.'/../Config/config.php'=>config_path('sah.php')
        ]);
    }

    public function register()
    {
        $this->app['maknz.slack'] = $this->app->share(function ($app) {
            return new Client(
                $app['config']->get('slack.endpoint'),
                [
                    'channel' => $app['config']->get('slack.channel'),
                    'username' => $app['config']->get('slack.username'),
                    'icon' => $app['config']->get('slack.icon'),
                    'link_names' => $app['config']->get('slack.link_names'),
                    'unfurl_links' => $app['config']->get('slack.unfurl_links')
                ],
                new Guzzle()
            );
        });
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['maknz.slack'];
    }
}
