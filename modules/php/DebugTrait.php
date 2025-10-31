<?php

namespace Bga\Games\Tembo;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Helpers\Log;
use Bga\Games\Tembo\Managers\Cards;

trait DebugTrait
{

  function tp()
  {
    $card = Cards::getAll()->where('internalId', 1)->first();
    $card->setLocation(LOCATION_BOARD);
    $card->setX(12);
    $card->setY(3);
    $card->setRotation(1);
  }


  function undoToStep($stepId)
  {
    $player = Players::getCurrent();
    Log::undoToStep($player->getId(), $stepId);
    $this->engProceed();
  }

  function resolveDebug()
  {
    Engine::resolveAction([]);
    Engine::proceed();
  }


  function engDisplay()
  {
    var_dump(Globals::getEngine());
  }

  function engProceed()
  {
    Engine::proceed();
  }
}
