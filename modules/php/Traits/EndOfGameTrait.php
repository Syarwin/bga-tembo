<?php

namespace Bga\Games\Tembo\Traits;

trait EndOfGameTrait
{
  public function stPreEndOfGame()
  {
    $this->gamestate->nextState('');
  }
}
