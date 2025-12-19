<?php

namespace Bga\Games\Tembo\Traits;

use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Game;
use Bga\Games\Tembo\Helpers\Log;
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
    $endGameReason = Globals::getEndGame();
    if ($endGameReason > 0) {
      $this->sendEndGameNotification($endGameReason);
      Game::get()->gamestate->jumpToState(ST_PRE_END_OF_GAME);
      return;
    }
    [$mustPlayMatriarch, $mustPlayLion, $endGame] = Players::getActive()->replenishCardsFromDeck();
    Log::checkpoint();
    if ($endGame) {
      Globals::setEndGame(END_GAME_NO_CARDS);
      $this->sendEndGameNotification(END_GAME_NO_CARDS);
      Game::get()->gamestate->jumpToState(ST_PRE_END_OF_GAME);
      return;
    }
    if ($mustPlayMatriarch) {
      $action = Players::isSolo() && !Globals::isSoloDiscardedSecondMatriarch() ? SOLO_DISCARD_SECOND_MATRIARCH : PLAY_MATRIARCH;
      Engine::setup(['action' => $action], ['method' => 'stEndOfTurn']);
      Engine::proceed();
    } else if ($mustPlayLion) {
      Engine::setup(['action' => ACTIVATE_LIONS], ['method' => 'stEndOfTurn']);
      Engine::proceed();
    } else {
      $this->nextPlayerCustomOrder('action');
    }
  }

  private function sendEndGameNotification(int $endGameReason): void
  {
    $player = Players::getActive();
    $msg = [
      END_GAME_NO_CARDS => clienttranslate('You lost the game because there\'s no cards left in the draw deck'),
      END_GAME_NO_ENERGY => clienttranslate('You lost the game because the Energy token reached space zero'),
      END_GAME_MATRIARCH => clienttranslate('You lost the game because both Lion meeples are in the same area as the Matriarch meeple'),
      END_GAME_NO_ELEPHANTS => clienttranslate('You lost the game because ${player_name} has zero Elephants left'),
    ][$endGameReason];
    if (Players::isSolo() && $endGameReason === END_GAME_NO_ELEPHANTS) {
      $msg = clienttranslate('You lost the game because you have zero Elephants left');
    }
    Notifications::message($msg, ['player' => $player]);
  }
}
