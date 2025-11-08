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
    return ['singleSpaces' => $board->getAllPossibleCoordsSingle($ignoreRough)];
  }

  public function isDoable(Player $player): bool
  {
    if (empty($this->getArgs()['singleSpaces'])) {
      $msg = clienttranslate('No space for a single elephant to place, ${player_name} cannot use this bonus');
      Notifications::message($msg, ['player' => $player]);
      return false;
    }
    return true;
  }

  public function actPlaceSingleElephant(int $x, int $y)
  {
    // TODO: Reuse the same method from UseCard.php
    $player = Players::getActive();
    $args = $this->getArgs();
    $coords = ['x' => $x, 'y' => $y, 'amount' => 1];
    if (!in_array($coords, $args['singleSpaces'])) {
      throw new \BgaVisibleSystemException("actPlaceSingleElephant: Incorrect coords x: {$x}, y: {$y}");
    }
    $elephants = Meeples::placeElephantsOnBoard($player->getId(), [$coords]);
    Notifications::elephantsPlaced($player, $elephants, null);
  }
}
