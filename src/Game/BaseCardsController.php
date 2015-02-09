<?php namespace Hopkins\SlackAgainstHumanity\Game;

use Hopkins\SlackAgainstHumanity\Game\Cards;
use Hopkins\SlackAgainstHumanity\Models\Card;
use Hopkins\SlackAgainstHumanity\Models\Player;
use Input;
use Response;
use Illuminate\Routing\Controller;

class BaseCardsController extends Controller{
    public function __construct(Cards $cards)
    {
        $this->cards = $cards;
    }

    public function deal()
    {
        $player = Player::with(['cards'])->where('user_name','=',Input::get('user_name'))->first();
        $this->cards->deal($player);
        return Response::json(['message'=>'success']);
    }

    public function play()
    {
        $player = Player::with(['cards'])->where('user_name','=',Input::get('user_name'))->first();
        $card = Card::find(Input::get('text'));
        $this->cards->play($player, $card);
        return Response::json(['message'=>'success']);
    }

    public function endRound()
    {
        $blackCard = Card::whereColor('black')->whereInPlay(1)->first();
        $judge = Player::with(['cards'])->find($blackCard->user_id);
        $whiteCards = Card::whereColor('white')->whereInPlay(1)->get()->toArray();
        $this->cards->endRound($judge, $whiteCards);
        return Response::json(['message'=>'success']);
    }

    public function choose()
    {
        /** @var \Hopkins\SlackAgainstHumanity\Models\Card $card */
        $card = Card::find(Input::get('text'));
        $player = Player::where('user_name','=',Input::get('user_name'))->first();
        $this->cards->choose($player, $card);
        return Response::json(['message'=>'success']);
    }

    public function quit()
    {
        Player::find(Input::get('id'))->update(['cards' => 0]);
        return Response::json(['message'=>'success']);
    }

    public function show()
    {
        $player = Player::where('user_name','=',Input::get('user_name'))->first();
        $cards = Card::whereUserId($player->id)->whereColor('white')->wherePlayed(0)->get();
        $this->cards->show($player, $cards);
        return Response::json(['message'=>'success']);
    }

    public function start(){
        $this->cards->start();
    }

    public function status(){
        $this->cards->status();
    }
}
