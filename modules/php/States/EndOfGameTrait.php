<?php

namespace Bga\Games\Tembo\States;

trait EndOfGameTrait
{
  public function stPreEndOfGame()
  {
    $this->gamestate->nextState('');
  }
}
