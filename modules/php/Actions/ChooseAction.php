<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Action;

class ChooseAction extends Action
{
  public function getState(): int
  {
    return ST_CHOOSE_ACTION;
  }


  public function argsChooseAction()
  {
    $player = Players::getActive();

    return [];
  }

  public function actChooseAction()
  {
    $player = Players::getActive();
    $args = $this->getArgs();
  }
}
