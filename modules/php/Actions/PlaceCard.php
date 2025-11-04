<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Action;
use Bga\Games\Tembo\Models\Board;
use Bga\Games\Tembo\Models\Player;

class PlaceCard extends Action
{
  public function getState(): int
  {
    return ST_PLACE_CARD;
  }

  public function argsPlaceCard()
  {
    $player = Players::getActive();

    return ['options' => (new Board())->getEmptySquares(), 'rotation' => $player->getRotation()];
  }

  public function actPlaceCard(int $cardId, int $x, int $y)
  {
    $activePlayer = Players::getActive();
    $args = $this->getArgs();
    $squaresWithCoords = array_filter($args['options'], fn($square) => $square['x'] === $x && $square['y'] === $y);
    if (empty($squaresWithCoords)) {
      throw new \BgaVisibleSystemException("actPlaceCard: Incorrect coords x: $x, y: $y");
    }
    if (count($squaresWithCoords) > 1) {
      throw new \BgaVisibleSystemException("actPlaceCard: There's more than one square at x: $x, y: $y. Should not happen");
    }
    $bonus = $squaresWithCoords[0]['type'];
    $allPlayers = Players::getAll();
    switch ($bonus) {
      case BONUS_ALL_GAIN_2:
        /** @var Player $pla */
        foreach ($allPlayers as $pla) {
          $pla->gainElephants(2);
        }
        break;
      case BONUS_ANOTHER_GAINS_4:
        // TODO: Add action to select player who gains 4
        break;
      case BONUS_YOU_5_ANOTHER_MINUS_2:
        $activePlayer->gainElephants(5);
        // TODO: Add action to select player who loses 2
        break;
      case BONUS_GAIN_3_PLACE_1_IGNORE_ROUGH:
        $activePlayer->gainElephants(3);
        // TODO: Add action to place 1 elephant
        break;
      default:
        throw new \BgaVisibleSystemException("actPlaceCard: Unknown bonus type $bonus. Should not happen");
    }

    Cards::placeOnBoard($cardId, $x, $y, $activePlayer->getRotation());
    Notifications::cardPlacedOnBoard($activePlayer, Cards::get($cardId));
  }
}
