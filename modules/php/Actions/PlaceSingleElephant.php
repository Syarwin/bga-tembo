<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Game;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Energy;
use Bga\Games\Tembo\Managers\Meeples;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Action;
use Bga\Games\Tembo\Models\Board;
use Bga\Games\Tembo\Models\Meeple;
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
    static::checkCoords($x, $y, $this->getArgs()['singleSpaces']);
    static::placeSingleElephant($x, $y);
  }

  public static function placeSingleElephant(int $x, int $y, ?int $cardId = null, bool $isMatriarch = false): void
  {
    $player = Players::getActive();
    $coords = ['x' => $x, 'y' => $y, 'amount' => 1];
    $elephants = Meeples::placeElephantsOnBoard($player->getId(), [$coords], $isMatriarch);
    $card = is_null($cardId) ? null : Cards::getSingle($cardId);
    if (!is_null($card)) {
      $card->setLocation(LOCATION_DISCARD);
    }
    if (!$isMatriarch) {
      Notifications::elephantsPlaced($player, $elephants, $card);
    }
    PlaceSingleElephant::verifySpacesBonuses($player, [$coords]);
  }

  public static function verifySpacesBonuses(Player $player, array $pattern)
  {
    $board = new Board();
    $pattern = $board->injectSpacesTypes($pattern);
    static::verifyOasis($player, $pattern);
    static::verifyTrees($player, $pattern, $board);
    static::verifyLandmarks($pattern, $board);
    static::verifyAndUnlockEndGameSpaces();
    static::verifyWinGame($pattern, $board);
  }

  private static function verifyOasis(Player $player, array $pattern): void
  {
    $cellsWithOasis = array_filter($pattern, fn($cell) => $cell['type'] === SPACE_OASIS);
    if (!empty($cellsWithOasis)) {
      $msg = clienttranslate('${player_name} covers a water spaces and gains 3 elephants');
      $player->gainElephants(3, $msg);
    }
  }

  private static function verifyTrees(Player $player, array $pattern, Board $board): void
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
            Energy::increase($energyAmount, '');
            Notifications::treesEaten($player, $cell['type'], $energyAmount);
          } else {
            Notifications::treesEaten($player, $cell['type'], 0);
          }
        }
        $processedCells[] = ['x' => $cell['x'], 'y' => $cell['y']];
      }
    }
  }

  private static function verifyLandmarks(array $pattern, Board $board)
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

  private static function verifyAndUnlockEndGameSpaces()
  {
    if (!Globals::isDestinationUnlocked()) {
      /** @var Meeple $landmark */
      $unVisitedLandmarks = array_filter(Meeples::getLandmarks(), fn($landmark
      ) => $landmark->getState() === STATE_STANDING);
      if (empty($unVisitedLandmarks)) {
        Globals::setDestinationUnlocked(true);
        $msg = clienttranslate('All landmarks have been visited, destination spaces have been unlocked!');
        Notifications::message($msg);
      }
    }
  }

  private static function verifyWinGame(array $pattern, Board $board)
  {
    if (Globals::isDestinationUnlocked()) {
      $cellsWithDestination = array_filter($pattern, fn($cell) => $cell['type'] === SPACE_DESTINATION);
      $winGame = count($cellsWithDestination) === 2;

      if (count($cellsWithDestination) === 1) {
        $winGame = $board->isBothDestinationHaveElephants();
      }
      if ($winGame) {
        foreach (Players::getAll() as $player) {
          $player->setScore(1);
        }
        Game::get()->gamestate->jumpToState(ST_PRE_END_OF_GAME);
      }
    }
  }

  public static function checkCoords(int $x, int $y, array $spaces): void
  {
    $spaces = array_map(fn($space) => ['x' => $space['x'], 'y' => $space['y']], $spaces);
    if (!in_array(['x' => $x, 'y' => $y], $spaces)) {
      throw new \BgaVisibleSystemException("checkCoords: Incorrect coords x: {$x}, y: {$y}");
    }
  }
}
