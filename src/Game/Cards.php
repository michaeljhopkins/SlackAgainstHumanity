<?php namespace Hopkins\SlackAgainstHumanity\Game;

use DB;
use Hopkins\SlackAgainstHumanity\Models\Card;
use Hopkins\SlackAgainstHumanity\Models\Player;
use Illuminate\Database\Eloquent\Collection;
use Slack;

class Cards
{
    public function __construct(Card $card, Player $player)
    {
        $this->card = $card;
        $this->player = $player;
    }
    public function choose($player, $card)
    {
        if ($player->is_judge) {
            $this->pickWinner($card->id);
            $this->endGameCommands($player);
        } else {
            Slack::to("@".$player->user_name)->send("You're not the judge!");
        }
    }

    public function endRound($judge, $whiteCards)
    {
        $blackCard = Card::whereColor('black')->whereInPlay(1)->first();
        Slack::send($blackCard->text);
        foreach ($whiteCards as $card) {
            Slack::send($card->id.". ".$card->text);
        }
        Slack::send("@".$judge->user_name." please respond with `/choose {id}`");
    }
    public function play($player, $card)
    {
        if (!$player->played && $player->id == $card->player_id) {
            $card->update(['in_play' => 1]);
            $player->update(['played' => 1, 'num_cards' => $player->num_cards - 1, 'idle' => 0]);
        } else {
            Slack::to("@".$player->user_name)->send("You've already played a card");
        }
        $this->endRoundCheck();
    }
    public function endRoundCheck()
    {
        if ($this->player->whereCah(1)->whereIsJudge(0)->wherePlayed(0)->whereIdle(0)->get()->isEmpty()) {
            $judge = $this->player->with(['cards'])->whereIsJudge(1)->first();
            $whiteCards = Card::whereColor('white')->whereInPlay(1)->get();
            $this->endRound($judge, $whiteCards);
        }
    }
    public function deal($player)
    {
        if ($player->cah == 0) {
            if (Player::whereCah(1)->whereIdle(0)->get()->count() == 2) {
                $player->update(['cah' => 1, 'idle' => 0]);
                $this->maintainEight();
                $this->pickNewBlackCard();
            } else {
                $player->update(['cah' => 1, 'idle' => 0]);
                $this->maintainEight();
            }
        } else {
            Slack::to("@".$player->user_name)->send("You've already been dealt");
        }
    }
    public function pickWinner($cardId)
    {
        /** @var \Hopkins\SlackAgainstHumanity\Models\Card $winningCard */
        $winningCard = Card::find($cardId);
        $winningPlayer = $this->player->find($winningCard->player_id);
        Slack::send("@".$winningPlayer->user_name."++ for ".$winningCard->text);
    }

    public function removeCardsFromPlay()
    {
        Card::whereInPlay(1)->update(['in_play' => 0, 'player_id' => null]);
        $this->player->wherePlayed(0)->whereIsJudge(0)->update(['idle' => 1]);
        $this->player->whereCah(1)->wherePlayed(1)->update(['played' => 0, 'idle' => 0]);
    }

    public function maintainEight()
    {
        $players = $this->player->with(['cards'])->whereCah(1)->get();
        /** @var \Hopkins\SlackAgainstHumanity\Models\$player */
        foreach ($players as $player) {
            if ($player->num_cards < 8) {
                $needAmount = 8-$player->num_cards;
                $cards = Card::randomWhites()->limit($needAmount)->get();
                foreach ($cards as $card) {
                    $card->update(['player_id' => $player->id, 'dealt' => 1]);
                    Slack::to("@".$player->user_name)->send($card->id.". ".$card->text);
                }
                $player->update(['num_cards' => 8]);
            }
        }
    }

    public function pickNewJudge()
    {
        $oldJudge = $this->player->whereIsJudge(1)->first();
        $player = $this->player->whereCah(1)->whereIsJudge(0)->whereIdle(0)->orderBy(DB::raw('RAND()'))->first();
        $player->update(['is_judge' => 1]);
        $oldJudge->update(['is_judge' => 0, 'idle' => 0]);
        Slack::send("The next judge is @".$player->user_name);
    }

    public function pickNewBlackCard()
    {
        $card = Card::randomNewBlack()->first();
        $judge = $this->player->whereIsJudge(1)->first();
        $card->update(['dealt' => 1, 'in_play' => 1, 'player_id' => $judge->id]);
        Slack::to("#cards")->send($card->text);
        Slack::to("#cards")->send("use `/cards {id}` to play a card. Only you will know which card is yours");
    }

    public function endGameCommands($player)
    {
        $this->removeCardsFromPlay();
        $this->maintainEight();
        $this->pickNewJudge($player->id);
        if ($this->player->whereCah(1)->whereIdle(0)->get()->count() >= 3) {
            $this->pickNewBlackCard();
        } else {
            Slack::to("#cards")->send("You need at least 3 players. Convince somebody else to not work and get dealt in");
        }
    }

    public function show($player, $cards)
    {
        foreach ($cards as $card) {
            Slack::to("@".$player->user_name)->send($card->id.". ".$card->text);
        }
    }
}
