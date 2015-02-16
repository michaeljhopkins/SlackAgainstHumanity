<?php namespace Hopkins\SlackAgainstHumanity\Game;

use DB;
use Hopkins\GamesBase\Interfaces\PlayerInterface;
use Hopkins\GamesBase\Models\Point;
use Hopkins\SlackAgainstHumanity\Models\Card;
use Slack;

class Cards
{
    /**
     * @var PlayerInterface
     */
    private $player;

    public function __construct(PlayerInterface $player)
    {

        $this->player = $player;
    }
    /*************************************************/
    /* These functions are invoked by the controller */
    /*************************************************/
    public function show($playerUserName)
    {
        $player = $this->player->whereUserName($playerUserName)->first();
        $cards = Card::wherePlayerId($player->id)->whereColor('white')->wherePlayed(0)->get();
        foreach ($cards as $card) {
            Slack::to('@' . $player->user_name)->send($card->id . '. ' . $card->text);
        }
    }

    public function start()
    {
        $user = $this->player->whereCah(1)->orderBy(DB::raw('RAND()'))->first();
        $user->update(['is_judge' => 1]);
        $card = Card::whereColor('black')->orderBy(DB::raw('RAND()'))->first();
        $card->update(['dealt' => 1, 'player_id' => $user->id, 'in_play' => 1]);
        Slack::to('#cards')->send('@' . $user->user_name . ' is the Judge');
        Slack::to('#cards')->send($card->text);
        Slack::to('#cards')->send('use /cards {id} to play a card. Only you will know which card is yours');
    }

    public function status()
    {
        $judge = $this->player->whereIsJudge(1)->first();
        Slack::to('#cards')->send('@' . $judge->user_name . ' is this rounds Judge.');
        $waitingFor = $this->player->whereCah(1)->wherePlayed(0)->whereIsJudge(0)->get();
        Slack::to('#cards')->send('We are currently waiting for - ');
        foreach ($waitingFor as $user) {
            Slack::to('#cards')->send('@' . $user->user_name);
        }
    }

    public function quit($username)
    {
        /** @var \Hopkins\GamesBase\Models\Player $player */
        /** @var \Hopkins\SlackAgainstHumanity\Models\Card $cards */
        $cards = Card::wherePlayerId($player->id)->get();
        $player = $this->player->whereUserName($username)->first();
        $player->update(['cah'=>0,'num_cards' => 0,'played' => 0, 'num_cards'=>0,'is_judge' => 0]);
        foreach($cards as $card){
            /** @var \Hopkins\SlackAgainstHumanity\Models\Card $card */
            $card->update(['played' => 1,'player_id' => null]);
        }

    }

    public function choose($playerUserName, $cardId)
    {
        $player = $this->player->whereUserName($playerUserName)->first();
        if ($player->is_judge) {
            $this->pickWinner($cardId);
            $this->endGameCommands($player);
        } else {
            Slack::to('@' . $player->user_name)->send('You\'re not the judge!');
        }
    }

    public function play($playerUserName, $cardId)
    {
        $player = $this->player->with(['cards'])->where('user_name', '=', $playerUserName)->first();
        $card = Card::find($cardId);
        if (!$player->played && $player->id == $card->player_id) {
            $card->update(['in_play' => 1]);
            $player->update(['played' => 1, 'num_cards' => $player->num_cards - 1, 'idle' => 0]);
        } else {
            Slack::to('@' . $player->user_name)->send('You\'ve already played a card');
        }
        $this->endRoundCheck();
    }

    public function deal($playerUsername)
    {
        $player = $this->player->with(['cards'])->whereUserName($playerUsername)->first();
        if ($player->cah == 1) {
            Slack::to('@' . $player->user_name)->send('You\'ve already been dealt');
        } else {
            $players = $this->player->whereCah(1)->whereIdle(0)->get()->count();
            if ($players == 2) {
                $player->update(['cah' => 1, 'idle' => 0]);
                $this->maintainEight();
                $this->start();
            } else {
                $player->update(['cah' => 1, 'idle' => 0]);
                $this->maintainEight();
            }
        }
    }

    private function pickWinner($cardId)
    {
        /**
         * @var \Hopkins\GamesBase\Models\Player $winningPlayer
         * @var \Hopkins\SlackAgainstHumanity\Models\Card $winningCard
         */
        $winningCard = Card::find($cardId);
        $winningPlayer = $this->player->find($winningCard->player_id);
        Point::create(['for' => $winningPlayer->user_name, 'modifier' => '1','reason' => $winningCard->text,'room' => 'cards']);
        Slack::to('#cards')->send('@' . $winningPlayer->user_name . '++ for ' . $winningCard->text);
    }


    /***************************************************/
    /* These functions are only called from this class */
    /***************************************************/
    private function endGameCommands(Player $player)
    {
        $this->removeCardsFromPlay();
        $this->maintainEight();
        $this->pickNewJudge($player->id);
        if ($this->player->whereCah(1)->whereIdle(0)->get()->count() >= 3) {
            $this->pickNewBlackCard();
        } else {
            Slack::to('#cards')->send('You need at least 3 players. Convince somebody else to not work and get dealt in');
        }
    }

    private function removeCardsFromPlay()
    {
        Card::whereInPlay(1)->update(['in_play' => 0, 'player_id' => null]);
        $this->player->wherePlayed(0)->whereIsJudge(0)->update(['idle' => 1]);
        $this->player->whereCah(1)->wherePlayed(1)->update(['played' => 0, 'idle' => 0]);
    }

    private function maintainEight()
    {
        $players = $this->player->with(['cards'])->whereCah(1)->get();
        foreach ($players as $player) {
            if ($player->num_cards < 8) {
                $needAmount = 8 - $player->num_cards;
                $cards = Card::randomWhites()->limit($needAmount)->get();
                foreach ($cards as $card) {
                    $card->update(['player_id' => $player->id, 'dealt' => 1]);
                    Slack::to('@' . $player->user_name)->send($card->id . '. ' . $card->text);
                }
                $player->update(['num_cards' => 8]);
            }
        }
    }

    private function pickNewJudge()
    {
        $oldJudge = $this->player->whereIsJudge(1)->first();
        $player = $this->player->whereCah(1)->whereIsJudge(0)->whereIdle(0)->orderBy(DB::raw('RAND()'))->first();
        $player->update(['is_judge' => 1]);
        $oldJudge->update(['is_judge' => 0, 'idle' => 0]);
        Slack::to('#cards')->send('The next judge is @' . $player->user_name);
    }

    private function pickNewBlackCard()
    {
        $card = Card::randomNewBlack()->first();
        $judge = $this->player->whereIsJudge(1)->first();
        $card->update(['dealt' => 1, 'in_play' => 1, 'player_id' => $judge->id]);
        Slack::to('#cards')->send($card->text);
        Slack::to('#cards')->send('use `/cards {id}` to play a card. Only you will know which card is yours');
    }

    private function endRoundCheck()
    {
        $players = $this->player->whereCah(1)->whereIsJudge(0)->wherePlayed(0)->whereIdle(0)->get();
        if (count($players) == 0) {
            $this->endRound();
        }
    }

    private function endRound()
    {
        $blackCard = Card::whereColor('black')->whereInPlay(1)->first();
        $judgeUserName = $this->player->with(['cards'])->find($blackCard->player_id)->user_name;
        $whiteCards = Card::whereColor('white')->whereInPlay(1)->get();
        Slack::to('#cards')->send($blackCard->text);
        foreach ($whiteCards as $card) {
            Slack::to('#cards')->send($card->id . '. ' . $card->text);
        }
        Slack::to('#cards')->send('@' . $judgeUserName . ' please respond with `/choose {id}`');
    }
}
