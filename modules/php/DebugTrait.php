<?php

namespace Bga\Games\Tembo;

use \Bga\GameFramework\Actions\CheckAction;
use Bga\Games\Tembo\Actions\ActivateLions;
use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Helpers\Log;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Models\Board;

trait DebugTrait
{

  #[CheckAction(false)]
  function actTestFitShape(int $x, int $y)
  {
    $shape = SHAPE_DIAG_UP;
    $rotation = 1;
    $ignoreRough = false;
    $nElephantAvailable = 10;

    $board = new Board();
    var_dump($board->getFitShapeElephantCost($shape, $x, $y, $rotation, $ignoreRough, $nElephantAvailable));
  }

  function tp()
  {
    var_dump(ActivateLions::getDistance(['x' => 4, 'y' => 16], ['x' => 6, 'y' => 5]));
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
