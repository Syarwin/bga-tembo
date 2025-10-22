<?php

namespace Bga\Games\Tembo\States;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Core\Stats;
use Bga\Games\Tembo\Managers\Players;

trait TurnTrait
{
  function stBeforeStartOfGame()
  {
    $this->initCustomDefaultTurnOrder('action', ST_TURN_ACTION, 0, true);
  }

  /**
   * Activate next player
   */
  function stTurnAction()
  {
    if (Globals::isEndOfGameTriggered()) {
      $this->gamestate->jumpToState(ST_PRE_END_OF_GAME);
      return;
    }


    // Clear globals

    // Give extra time
    $player = Players::getActive();
    self::giveExtraTime($player->getPId());
    // Stats::incPlayerTurns($player);

    // Inserting leaf CHOOSE_ACTION
    $node = [
      'action' => CHOOSE_ACTION,
      'flag' => 'chooseAction',
      'pId' => $player->getId(),
    ];
    Engine::setup($node, ['method' => 'stEndOfTurn']);
    Engine::proceed();
  }

  /*******************************
   ********************************
   ********** END OF TURN *********
   ********************************
   *******************************/

  /**
   * End of turn : replenish and check break
   */
  function stEndOfTurn()
  {
    $this->nextPlayerCustomOrder('action');
  }
}
