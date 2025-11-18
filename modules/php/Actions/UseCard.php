<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\EventTiles;
use Bga\Games\Tembo\Managers\Meeples;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Managers\SupportTokens;
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
    $hand = $player->getHand();
    $matriarch = $hand->filter(fn($card) => $card->isMatriarch());
    $hand = $hand->filter(fn($card) => !$card->isMatriarch());
    $board = new Board();

    $supportTokenRotationUsed = ($this->getCtxArg('supportRotate') ?? false);
    $rotatableCards = $supportTokenRotationUsed ? $hand : $hand->filter(fn($card) => $card->canBeRotated());

    return [
      'cardIds' => $hand->getIds(),
      'rotatableCardIds' => EventTiles::isRotatableCardsAllowed() ? $rotatableCards->getIds() : [],
      'patterns' => $board->getAllPossiblePatterns($hand, $player->getRotation(), $player->getRestedElephantsAmount(), $supportTokenRotationUsed),
      'squares' => $board->getEmptySquares(),
      'rotation' => $player->getRotation(),
      'singleSpaces' => $board->getAllPossibleCoordsSingle(),
      'supportTokens' => SupportTokens::get(),

      'matriarchIds' => $matriarch->empty() ? null : $matriarch->getIds(),
      'descSuffix' => is_null($matriarch) ? '' : 'matriarch',
    ];
  }

  public function actPlaceCard(int $cardId, int $x, int $y, int $rotation)
  {
    $activePlayer = Players::getActive();
    $args = $this->getArgs();
    $squaresWithCoords = array_values(array_filter($args['squares'], fn($sq) => $sq['x'] === $x && $sq['y'] === $y));
    if (empty($squaresWithCoords)) {
      throw new \BgaVisibleSystemException("actPlaceCard: Incorrect coords x: $x, y: $y");
    }
    if (count($squaresWithCoords) > 1) {
      throw new \BgaVisibleSystemException("actPlaceCard: There's more than one square at x: $x, y: $y. Should not happen");
    }
    if ($rotation != $args['rotation'] && !in_array($cardId, $args['rotatableCardIds'])) {
      throw new \BgaVisibleSystemException("actPlaceCard: You can't place that card with this rotation. Should not happen");
    }

    // Place the card
    Cards::placeOnBoard($cardId, $x, $y, $rotation);
    Notifications::cardPlacedOnBoard($activePlayer, Cards::get($cardId));
    ActivateLions::checkIfLionIsHereAndMove($x, $y);

    // Any bonus ?
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
      case BONUS_YOU_GAIN_4: // This must be a solo game
        /** @var Player $pla */
        foreach ($allPlayers as $pla) {
          $pla->gainElephants(4);
        }
        break;
      default:
        throw new \BgaVisibleSystemException("actPlaceCard: Unknown bonus type $bonus. Should not happen");
    }
  }

  public function actPlaceElephants(int $cardId, int $patternIndex)
  {
    $patterns = $this->getArgs()['patterns'];
    if (!isset($patterns[$cardId])) {
      throw new \BgaVisibleSystemException("actPlaceElephants: Cannot find patterns for card $cardId");
    }
    if (!isset($patterns[$cardId][$patternIndex])) {
      throw new \BgaVisibleSystemException("actPlaceElephants: Incorrect pattern index $patternIndex");
    }
    $pattern = $patterns[$cardId][$patternIndex];
    $activePlayer = Players::getActive();
    $elephants = Meeples::placeElephantsOnBoard($activePlayer->getId(), $pattern);
    Cards::move($cardId, LOCATION_DISCARD);
    Notifications::elephantsPlaced($activePlayer, $elephants, Cards::getSingle($cardId));
    PlaceSingleElephant::verifySpacesBonuses($activePlayer, $pattern);
  }

  public function actPlaceSingleElephant(int $x, int $y, ?int $cardId = null): void
  {
    PlaceSingleElephant::checkCoords($x, $y, $this->getArgs()['singleSpaces']);
    PlaceSingleElephant::placeSingleElephant($x, $y, $cardId);
  }

  public function actUseSupportToken(int $option): void
  {
    $flow = SupportTokens::spend(Players::getActive(), $option);
    if (!is_null($flow)) {
      $this->insertAsChild($flow);
    }

    // Duplicate the action
    $args = [];
    if ($option == SUPPORT_ROTATE) {
      $args['supportRotate'] = true;
    }
    $this->duplicateAction($args);
  }

  public function actPlayMatriarch(int $x, int $y)
  {
    $player = Players::getActive();
    PlaceSingleElephant::checkCoords($x, $y, $this->getArgs()['singleSpaces']);
    PlayMatriarch::play($x, $y, $player);
  }
}
