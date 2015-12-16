<?php
/**
 * Created by PhpStorm.
 * User: m
 * Date: 12/14/15
 * Time: 4:07 PM
 */

namespace Hopkins\SlackAgainstHumanity\Game;


use Hopkins\SlackAgainstHumanity\Exceptions\CzarNeedsToChooseException;
use Hopkins\SlackAgainstHumanity\Exceptions\NotAllowedToPlayException;
use Hopkins\SlackAgainstHumanity\Exceptions\WaitingForPlayersException;

class Round
{
    public function play($playerId, $cardId){
        $status = $this->loop($playerId,$cardId);
        if($status == 'waiting for players'){
            throw new WaitingForPlayersException('Waiting For Players');
        }
        elseif($status == 'all in'){
            throw new CzarNeedsToChooseException('Czar Needs To Choose');
        }
    }

    public function loop($playerId,$cardId){
        $this->maintainTen();
        if(!$this->canPlay($playerId)){
            throw new NotAllowedToPlayException('It\'s not your turn to play!');
        }
        $this->playCard($cardId);
        return $this->currentRoundStatus();
    }

    public function choose($cardId){}

    private function maintainTen(){}

    private function canPlay($playerId){}

    private function playCard($cardId){}

    private function currentRoundStatus(){}

    private function chooseWinningCard($cardId){}

    private function distributePoint($playerId){}

    private function endRound(){}

    private function clearBoard(){}

    private function pickCzar($previousCzarId){}

    private function pickBlackCard(){}

}