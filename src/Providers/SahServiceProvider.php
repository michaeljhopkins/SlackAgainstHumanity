<?php namespace Hopkins\SlackAgainstHumanity\Providers;

use Illuminate\Support\ServiceProvider;

class SahServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $createCardsTable = 'create_cards_table.php';
        $createPlayersTable = 'create_players_table.php';
        $cardsMigration = __DIR__.'/../Database/migrations'.$createCardsTable;
        $playersMigration = __DIR__.'/../Database/migrations'.$createPlayersTable;
        $this->publishes([
            $cardsMigration => $this->app['path.database'].'/migrations/'. $this->getDatePrefix().$createCardsTable,
            $playersMigration => $this->app['path.database'].'/migrations/'. $createPlayersTable
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
