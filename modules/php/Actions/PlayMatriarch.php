<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Energy;
use Bga\Games\Tembo\Managers\Meeples;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Action;
use Bga\Games\Tembo\Models\Board;
use Bga\Games\Tembo\Models\Player;

class PlayMatriarch extends Action
{
  public function getState(): int
  {
    return ST_MATRIARCH;
  }

  public function argsPlayMatriarch()
  {
    $player = Players::getActive();
    $board = new Board();
    return [
      'cards' => $player->getMatriarchCards(),
      'singleSpaces' => $board->getAllPossibleCoordsSingle(),
    ];
  }

  public function actPlayMatriarchForced(int $x, int $y)
  {
    PlaceSingleElephant::checkCoords($x, $y, $this->getArgs()['singleSpaces']);
    static::play($x, $y, Players::getActive());
  }

  public function actLeaveMatriarch()
  {
    static::play(null, null, Players::getActive());
  }

  public static function play(?int $x, ?int $y, Player $player)
  {
    // 0. Move all Matriarch cards to discard
    $matriarchCards = $player->getMatriarchCards();
    Cards::move($matriarchCards->getIds(), LOCATION_DISCARD);
    // 1. Move the Matriarch
    if (!is_null($x) && !is_null($y)) {
      PlaceSingleElephant::placeSingleElephant($x, $y, null, true);
    }
    $matriarchCardsCount = $matriarchCards->count();
    if ($matriarchCardsCount === 0 || $matriarchCardsCount > 2) {
      throw new \BgaVisibleSystemException("actPlayMatriarch: Amount of matriarch cards is incorrect: $matriarchCardsCount");
    }
    // 2. Gather the Herd
    Meeples::gatherHerd();
    // 2.5. Notify players about the matriarch action before the energy notification
    Notifications::matriarchAction($player, $matriarchCards->toArray());
    // 3. Lose energy
    $energyToSpend = $matriarchCards->count() === 2 ? 5 : 2;
    Energy::decrease($energyToSpend);
    // 4. Refresh Trees
    Meeples::refreshTrees();
  }
}
