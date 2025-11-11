<?php

namespace Bga\Games\Tembo\Traits;

use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Player;

trait TurnTrait
{
  function stBeforeStartOfGame()
  {
    $this->initCustomDefaultTurnOrder('action', ST_TURN_ACTION, 0, true);
    /** @var Player $player */
    foreach (Players::getAll() as $player) {
      if ($player->getRotation() === -1) {
        $player->setRotation(bga_rand(0, 3));
      }
    }
  }

  /**
   * Activate next player
   */
  function stTurnAction()
  {
    // TODO
    // if (Globals::isEndOfGameTriggered()) {
    //   $this->gamestate->jumpToState(ST_PRE_END_OF_GAME);
    //   return;
    // }


    // Clear globals

    // Give extra time
    $player = Players::getActive();
    self::giveExtraTime($player->getId());
    // Stats::incPlayerTurns($player);

    // Inserting leaf CHOOSE_ACTION
    $node = [
      'action' => USE_CARD,
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
    $mustPlayMatriarch = Players::getActive()->replenishCardsFromDeck();
    if ($mustPlayMatriarch) {
      Engine::setup(['action' => PLAY_MATRIARCH], ['method' => 'stEndOfTurn']);
      Engine::proceed();
    } else {
      $this->nextPlayerCustomOrder('action');
    }
  }
}
