<?php

namespace Bga\Games\Tembo\States;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Board;

trait SetupTrait
{
  /*
   * setupNewGame:
   */
  protected function setupNewGame($players, $options = [])
  {
    Globals::setupNewGame($players, $options);
    Players::setupNewGame($players, $options);
    Board::setupNewGame();
    // Stats::checkExistence();

    $this->activeNextPlayer();
  }

  public function stSetupBranch()
  {
    $this->gamestate->nextState('debug');
  }

  public function stSetupCards()
  {
    Cards::setupNewGame();
    $this->gamestate->nextState('debug');
  }
}
