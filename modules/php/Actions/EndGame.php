<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Game;
use Bga\Games\Tembo\Models\Action;

class EndGame extends Action
{
  public function getState(): int
  {
    return ST_GENERIC_AUTOMATIC;
  }

  public function stEndGame()
  {
    if (Globals::isEndGame()) {
      Game::get()->gamestate->jumpToState(ST_PRE_END_OF_GAME);
    }
  }
}
