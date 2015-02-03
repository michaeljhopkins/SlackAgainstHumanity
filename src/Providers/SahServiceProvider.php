<?php namespace Hopkins\SlackAgainstHumanity\Providers;

use Illuminate\Support\ServiceProvider;
use Maknz\Slack\Client;
use GuzzleHttp\Client as Guzzle;

class SahServiceProvider extends ServiceProvider
{

    public function boot()
    {
        include __DIR__ . '/../Http/routes.php';

        $createCardsTable = 'create_cards_table.php';
        $createPlayersTable = 'create_players_table.php';
        $this->publishes([
            __DIR__ . '/../Database/migrations/'. $createCardsTable => $this->app['path.database'].'/database/'. $this->getDatePrefix().$createCardsTable,
            __DIR__ . '/../Database/migrations/'. $createPlayersTable => $this->app['path.database'].'/database/'.$this->getDatePrefix().$createPlayersTable,
            __DIR__.'/../Database/seeds' => $this->app['path.database'].'/seeds'
        ]);
    }

    public function register()
    {

    }
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }
}
