<?php namespace Hopkins\SlackAgainstHumanity\Game;

use Hopkins\SlackAgainstHumanity\Models\Card;
use Hopkins\SlackAgainstHumanity\Models\Player;
use Illuminate\Support\Facades\Response;
use Input;

class Handler
{
    public function __construct(Cards $cards)
    {
        $this->cards = $cards;
    }

    public function deal()
    {
        $data = Input::all();
        $player = Player::with(['cards'])->where('user_name','=',$data['user_name'])->first();
        $this->cards->deal($player);
        return Response::json(['message'=>'success']);
    }

    public function play()
    {
        $data = Input::all();
        $player = Player::with(['cards'])->where('user_name','=',$data['user_name'])->first();
        $card = Card::find($data['text']);
        $this->cards->play($player, $card);
    }

    public function endRound()
    {
        $data = Input::all();
        $blackCard = Card::whereColor('black')->whereInPlay(1)->first();
        $judge = Player::with(['cards'])->find($blackCard->user_id);
        $whiteCards = Card::whereColor('white')->whereInPlay(1)->get();
        $this->cards->endRound($judge, $blackCard, $whiteCards);
    }

    public function choose()
    {
        $data = Input::all();
        /** @var \Hopkins\SlackAgainstHumanity\Models\Card $card */
        $card = Card::find($data['text']);
        $player = Player::where('user_name','=',$data['user_name'])->first();
        $this->cards->choose($player, $card);
    }

    public function quit()
    {
        $data = Input::all();
        Player::find($data['id'])->update(['cards' => 0]);
    }

    public function show()
    {
        $data = Input::all();
        $player = Player::wherewhere('user_name','=',$data['user_name'])->first();
        $cards = Card::whereUserId($player->id)->whereColor('white')->wherePlayed(0)->get();
        $this->cards->show($player, $cards);
    }
}
