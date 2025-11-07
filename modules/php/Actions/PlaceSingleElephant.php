<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Managers\Meeples;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Action;
use Bga\Games\Tembo\Models\Board;
use Bga\Games\Tembo\Models\Player;

class PlaceSingleElephant extends Action
{
  public function getState(): int
  {
    return ST_PLACE_SINGLE_ELEPHANT;
  }

  public function argsPlaceSingleElephant()
  {
    $board = new Board();
    $ignoreRough = $this->getCtxArg('ignoreRough') ?? false;
    return ['options' => $board->getAllPossibleCoordsSingle($ignoreRough)];
  }

  public function isDoable(Player $player): bool
  {
    if (empty($this->getArgs()['options'])) {
      $msg = clienttranslate('No space for a single elephant to place, ${player_name} cannot use this bonus');
      Notifications::message($msg, ['player' => $player]);
      return false;
    }
    return true;
  }

  public function actPlaceSingleElephant(int $x, int $y)
  {
    $player = Players::getActive();
    $args = $this->getArgs();
    $coords = ['x' => $x, 'y' => $y];
    if (!in_array($coords, $args['options'])) {
      throw new \BgaVisibleSystemException("actPlaceSingleElephant: Incorrect coords x: {$x}, y: {$y}");
    }
    $elephants = Meeples::placeElephantsOnBoard($player->getId(), [$coords]);
    // TODO: Check for oasis or trees
    Notifications::elephantsPlaced($player, $elephants);
  }
}
