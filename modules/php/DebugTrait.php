<?php

namespace Bga\Games\Tembo;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Managers\FlowerCards;
use Bga\Games\Tembo\Managers\Players;

trait DebugTrait
{
  public function testZones()
  {
    $player = Players::getCurrent();
    var_dump($player->board()->getZones());
  }

  public function tp()
  {
    // $card = FlowerCards::getSingle(43);
    // $player = Players::getCurrent();
    // $board = $player->board();
    // var_dump($board->canPlayCard($card));
    $isStandard = empty(Globals::getEcosystems());
    var_dump($isStandard);
  }
}
