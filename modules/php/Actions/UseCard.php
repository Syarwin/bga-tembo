<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Action;
use Bga\Games\Tembo\Models\Board;
use Bga\Games\Tembo\Models\Player;

class UseCard extends Action
{
  public function getState(): int
  {
    return ST_USE_CARD;
  }

  public function argsUseCard()
  {
    $player = Players::getActive();

    return [
      'cardIds' => $player->getHand()->getIds(),
      'patterns' => [], // For each cards, compute the list of positions to place the corresponding elephants
      'squares' => (new Board())->getEmptySquares(),
      'rotation' => $player->getRotation()
    ];
  }

  public function actPlaceCard(int $cardId, int $x, int $y)
  {
    $activePlayer = Players::getActive();
    $args = $this->getArgs();
    $squaresWithCoords = array_values(array_filter($args['squares'], fn($square) => $square['x'] === $x && $square['y'] === $y));
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
        Engine::insertAsChild(['action' => PLAYER_GAIN_LOSE_ELEPHANTS, 'args' => ['amount' => 4]]);
        break;
      case BONUS_YOU_5_ANOTHER_MINUS_2:
        $activePlayer->gainElephants(5);
        Engine::insertAsChild(['action' => PLAYER_GAIN_LOSE_ELEPHANTS, 'args' => ['amount' => -2]]);
        break;
      case BONUS_GAIN_3_PLACE_1_IGNORE_ROUGH:
        $activePlayer->gainElephants(3);
        Engine::insertAsChild(['action' => PLACE_SINGLE_ELEPHANT, 'args' => ['ignoreRough' => true]]);
        break;
      default:
        throw new \BgaVisibleSystemException("actPlaceCard: Unknown bonus type $bonus. Should not happen");
    }

    Cards::placeOnBoard($cardId, $x, $y, $activePlayer->getRotation());
    Notifications::cardPlacedOnBoard($activePlayer, Cards::get($cardId));
  }
}
