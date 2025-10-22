<?php

namespace Bga\Games\Tembo;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Helpers\Log;

trait DebugTrait
{

  function tp() {}


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
