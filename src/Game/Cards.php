<?php namespace Hopkins\SlackAgainstHumanity\Game;

use DB;
use Hopkins\GamesBase\Models\Player;
use Hopkins\SlackAgainstHumanity\Models\Card;
use Slack;

class Cards
{
    public function choose($playerUserName, $cardId)
    {
        $player = Player::whereUserName($playerUserName)->first();
        $card = Card::find($cardId);
        if ($player->is_judge) {
            $this->pickWinner($card->id);
            $this->endGameCommands($player);
        } else {
            Slack::to('@' . $player->user_name)->send('You\'re not the judge!');
        }
    }

    public function pickWinner($cardId)
    {
        /** @var \Hopkins\GamesBase\Models\Player $winningPlayer */
        /** @var \Hopkins\SlackAgainstHumanity\Models\Card $winningCard */
        $winningCard = Card::find($cardId);
        $winningPlayer = Player::find($winningCard->player_id);
        Slack::to('#cards')->send('@' . $winningPlayer->user_name . '++ for ' . $winningCard->text);
    }

    public function endGameCommands(Player $player)
    {
        $this->removeCardsFromPlay();
        $this->maintainEight();
        $this->pickNewJudge($player->id);
        if (Player::whereCah(1)->whereIdle(0)->get()->count() >= 3) {
            $this->pickNewBlackCard();
        } else {
            Slack::to('#cards')->send('You need at least 3 players. Convince somebody else to not work and get dealt in');
        }
    }

    public function removeCardsFromPlay()
    {
        Card::whereInPlay(1)->update(['in_play' => 0, 'player_id' => null]);
        Player::wherePlayed(0)->whereIsJudge(0)->update(['idle' => 1]);
        Player::whereCah(1)->wherePlayed(1)->update(['played' => 0, 'idle' => 0]);
    }

    public function maintainEight()
    {
        /** @var \Hopkins\GamesBase\Models\Player $player */
        /** @var \Hopkins\SlackAgainstHumanity\Models\Card $card */
        $players = Player::with(['cards'])->whereCah(1)->get();
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

    public function pickNewJudge()
    {
        $oldJudge = Player::whereIsJudge(1)->first();
        $player = Player::whereCah(1)->whereIsJudge(0)->whereIdle(0)->orderBy(DB::raw('RAND()'))->first();
        $player->update(['is_judge' => 1]);
        $oldJudge->update(['is_judge' => 0, 'idle' => 0]);
        Slack::to('#cards')->send('The next judge is @' . $player->user_name);
    }

    public function pickNewBlackCard()
    {
        $card = Card::randomNewBlack()->first();
        $judge = Player::whereIsJudge(1)->first();
        $card->update(['dealt' => 1, 'in_play' => 1, 'player_id' => $judge->id]);
        Slack::to('#cards')->send($card->text);
        Slack::to('#cards')->send('use `/cards {id}` to play a card. Only you will know which card is yours');
    }

    public function play($playerUserName, $cardId)
    {
        $player = Player::with(['cards'])->where('user_name', '=', $playerUserName)->first();
        $card = Card::find($cardId);
        if (!$player->played && $player->id == $card->player_id) {
            $card->update(['in_play' => 1]);
            $player->update(['played' => 1, 'num_cards' => $player->num_cards - 1, 'idle' => 0]);
        } else {
            Slack::to('@' . $player->user_name)->send('You\'ve already played a card');
        }
        $this->endRoundCheck();
    }

    public function endRoundCheck()
    {
        $players = Player::whereCah(1)->whereIsJudge(0)->wherePlayed(0)->whereIdle(0)->get();
        if (count($players) == 0) {
            $this->endRound();
        }
    }

    public function endRound()
    {
        $blackCard = Card::whereColor('black')->whereInPlay(1)->first();
        $judgeUserName = Player::with(['cards'])->find($blackCard->player_id)->user_name;
        $whiteCards = Card::whereColor('white')->whereInPlay(1)->get();
        Slack::to('#cards')->send($blackCard->text);
        foreach ($whiteCards as $card) {
            Slack::to('#cards')->send($card->id . '. ' . $card->text);
        }
        Slack::to('#cards')->send('@' . $judgeUserName . ' please respond with `/choose {id}`');
    }

    public function deal($playerUsername)
    {
        $player = Player::with(['cards'])->whereUserName($playerUsername)->first();
        if ($player->cah == 1) {
            Slack::to('@' . $player->user_name)->send('You\'ve already been dealt');
        } else {
            $players = Player::whereCah(1)->whereIdle(0)->get()->count();
            if ($players == 2) {
                $player->update(['cah' => 1, 'idle' => 0]);
                $this->maintainEight();
                $this->pickNewBlackCard();
            } else {
                $player->update(['cah' => 1, 'idle' => 0]);
                $this->maintainEight();
            }
        }
    }

    public function show($playerUserName)
    {
        $player = Player::whereUserName($playerUserName)->first();
        $cards = Card::wherePlayerId($player->id)->whereColor('white')->wherePlayed(0)->get();
        foreach ($cards as $card) {
            Slack::to('@' . $player->user_name)->send($card->id . '. ' . $card->text);
        }
    }

    public function start()
    {
        /** @var \Hopkins\SlackAgainstHumanity\Models\Card $card */
        /** @var \Hopkins\GamesBase\Models\Player $user */
        $user = Player::whereCah(1)->orderBy(DB::raw('RAND()'))->first();
        $user->update(['is_judge' => 1]);
        $card = Card::whereColor('black')->orderBy(DB::raw('RAND()'))->first();
        $card->update(['dealt' => 1, 'player_id' => $user->id, 'in_play' => 1]);
        Slack::to('#cards')->send('@' . $user->user_name . ' is the Judge');
        Slack::to('#cards')->send($card->text);
        Slack::to('#cards')->send('use /cards {id} to play a card. Only you will know which card is yours');
    }

    public function status()
    {
        $judge = Player::whereIsJudge(1)->first();
        Slack::to('#cards')->send('@' . $judge->user_name . ' is this rounds Judge.');
        $waitingFor = Player::whereCah(1)->wherePlayed(0)->whereIsJudge(0)->get();
        Slack::to('#cards')->send('We are currently waiting for - ');
        foreach ($waitingFor as $user) {
            Slack::to('#cards')->send('@' . $user->user_name);
        }
    }
}
