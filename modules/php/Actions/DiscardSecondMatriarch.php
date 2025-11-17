<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Action;

class DiscardSecondMatriarch extends Action
{
  public function getState(): int
  {
    return ST_DISCARD_SECOND_MATRIARCH;
  }

  public function actDiscardSecondMatriarch()
  {
    $player = Players::getActive();
    $matriachCard = $player->getMatriarchCards()->rand();
    $matriachCard->setLocation(LOCATION_DISCARD);
    Globals::setSoloDiscardedSecondMatriarch(true);
    Notifications::matriarchCardDiscarded($player, $matriachCard->getId());
  }

  public function actDoNotDiscardSecondMatriarch()
  {
    Engine::insertAsChild(['action' => PLAY_MATRIARCH]);
  }
}
