<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Core\Engine;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Energy;
use Bga\Games\Tembo\Managers\Meeples;
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
    $player = Players::getActive(); // TODO: This should be current, not active. But this leads to a table not able to start
    $hand = $player->getHand();
    $board = new Board();

    return [
      'cardIds' => $hand->getIds(),
      'patterns' => $board->getAllPossiblePatterns($hand, $player->getRotation(), $player->getRestedElephantsAmount()),
      'squares' => $board->getEmptySquares(),
      'rotation' => $player->getRotation(),
      'singleSpaces' => $board->getAllPossibleCoordsSingle(),
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
    Notifications::elephantsPlaced($activePlayer, $elephants, $cardId);
    Cards::move($cardId, LOCATION_DISCARD);
    $this->verifySpacesBonuses($activePlayer, $pattern);
  }

  public function actPlaceSingleElephant(int $x, int $y, ?int $cardId = null): void
  {
    $player = Players::getActive();
    $args = $this->getArgs();
    $coords = ['x' => $x, 'y' => $y, 'amount' => 1];
    if (!in_array($coords, $args['singleSpaces'])) {
      throw new \BgaVisibleSystemException("actPlaceSingleElephant: Incorrect coords x: {$x}, y: {$y}");
    }
    $elephants = Meeples::placeElephantsOnBoard($player->getId(), [$coords]);
    if (!is_null($cardId)) {
      Cards::move($cardId, LOCATION_DISCARD);
    }
    Notifications::elephantsPlaced($player, $elephants, $cardId);
    $this->verifySpacesBonuses($player, [$coords]);
  }

  public function verifySpacesBonuses(Player $player, array $pattern)
  {
    $board = new Board();
    $pattern = $board->injectSpacesTypes($pattern);
    $this->verifyOasis($player, $pattern);
    $this->verifyTrees($player, $pattern, $board);
    $this->verifyLandmarks($pattern, $board);
  }

  private function verifyOasis(Player $player, array $pattern): void
  {
    $cellsWithOasis = array_filter($pattern, fn($cell) => $cell['type'] === SPACE_OASIS);
    if (!empty($cellsWithOasis)) {
      $msg = clienttranslate('${player_name} covers a water spaces and gains 3 elephants');
      $player->gainElephants(3, $msg);
    }
  }

  private function verifyTrees(Player $player, array $pattern, Board $board): void
  {
    $allTreesTypes = [SPACE_TREE_GREEN, SPACE_TREE_RED, SPACE_TREE_BROWN, SPACE_TREE_TEAL];
    $cellsWithTrees = array_filter($pattern, fn($cell) => in_array($cell['type'], $allTreesTypes));
    if (!empty($cellsWithTrees)) {
      $processedCells = [];
      foreach ($cellsWithTrees as $cell) {
        $correspondingCell = $board->getCorrespondingTreeSpace($cell);
        if (!in_array($correspondingCell, $processedCells) && !Meeples::getOnCell($correspondingCell)->empty()) {
          $successful = Meeples::layTree($cell['type']);
          if ($successful) {
            $energyAmount = $cell['type'] === SPACE_TREE_GREEN ? 2 : 1;
            Energy::increase($energyAmount);
            Notifications::treesEaten($player, $cell['type'], $energyAmount);
          } else {
            Notifications::treesEaten($player, $cell['type'], 0);
          }
        }
        $processedCells[] = ['x' => $cell['x'], 'y' => $cell['y']];
      }
    }
  }

  private function verifyLandmarks(array $pattern, Board $board)
  {
    $cellsWithLandmarks = array_filter($pattern, fn($cell) => $cell['type'] === SPACE_LANDMARK);
    if (!empty($cellsWithLandmarks)) {
      $processedLandmarks = [];
      foreach ($cellsWithLandmarks as $cell) {
        $landmark = $board->getLandmarkByCell($cell);
        if (!in_array($landmark, $processedLandmarks)) {
          $correspondingCells = $board->getCorrespondingLandmarkSpaces($cell);
          $landmarkFilled = true;
          foreach ($correspondingCells as $correspondingCell) {
            if (Meeples::getOnCell($correspondingCell)->empty()) {
              $landmarkFilled = false;
              break;
            }
          }
          if ($landmarkFilled) {
            $landmarkType = Meeples::layLandmark($landmark);
            Notifications::landmarkVisited($landmarkType);
          }
          $processedLandmarks[] = $landmark;
        }
      }
    }
  }
}
