<?php

namespace Bga\Games\Tembo\States;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Managers\Players;

trait SetupTrait
{
  /*
   * setupNewGame:
   */
  protected function setupNewGame($players, $options = [])
  {
    Globals::setupNewGame($players, $options);
    Players::setupNewGame($players, $options);
    // Stats::checkExistence();

    $this->activeNextPlayer();
  }

  public function stSetupBranch()
  {
    $this->gamestate->nextState('debug');
  }
}
