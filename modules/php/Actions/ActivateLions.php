<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Meeples;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Action;
use Bga\Games\Tembo\Models\Board;
use Bga\Games\Tembo\Models\Meeple;
use Bga\Games\Tembo\Models\Player;

class ActivateLions extends Action
{
  const DIRECTIONS = [ // Sorted following the lion compass image
    ['x' => 0, 'y' => -3], // up
    ['x' => 3, 'y' => 0], // right
    ['x' => 0, 'y' => 3], // down
    ['x' => -3, 'y' => 0], // left
  ];

  public function getState(): int
  {
    return ST_GENERIC_AUTOMATIC;
  }

  public function stActivateLions()
  {
    $activePlayer = Players::getActive();
    $cards = $activePlayer->getLionCards();
    Cards::move($cards->getIds(), LOCATION_DISCARD);
    static::activate(Meeples::getLions(), $cards, $activePlayer);
    return true;
  }

  private static function findAvailableDirections(array $lionCoords, Board $board)
  {
    $availableDirections = array_filter(self::DIRECTIONS, function ($direction) use ($lionCoords, $board) {
      $coords = ['x' => $lionCoords['x'] + $direction['x'], 'y' => $lionCoords['y'] + $direction['y']];
      return $board->isSquareExist($coords);
    });
    return array_values($availableDirections);
  }

  private static function findClosest($target, $points): array
  {
    $closest = null;
    $minDistance = PHP_FLOAT_MAX;
    // Convert real coords to "square" coords (top-left corner of each square)
    $target = static::convertToSquareCoords($target);

    foreach ($points as $point) {
      $point = static::convertToSquareCoords($point);
      if ($point === $target) {
        return $point;
      }

      $distance = static::getDistance($target, $point);

      if ($distance < $minDistance) {
        $minDistance = $distance;
        $closest = $point;
      } elseif ($distance === $minDistance) {
        // Lion compass says the priorities are top, then right, down and left. If two squares are equally close, the compass starts working
        if ($point['y'] < $closest['y']) {
          $closest = $point;
        } elseif ($point['y'] === $closest['y'] && $point['x'] > $closest['x']) {
          $closest = $point;
        } // Otherwise $closest is the priority
      }
    }

    return ['x' => $closest['x'] * 3, 'y' => $closest['y'] * 3];
  }

  private static function convertToSquareCoords($cell, $divideBy3 = true)
  {
    $divider = $divideBy3 ? 3 : 1;
    $cell['x'] = ($cell['x'] - ($cell['x'] % 3)) / $divider;
    $cell['y'] = ($cell['y'] - ($cell['y'] % 3)) / $divider;
    return $cell;
  }

  private static function getDistance($target, $point): int
  {
    return abs($point['x'] - $target['x']) + abs($point['y'] - $target['y']);
  }

  private static function findDirectionsMakingLionCloser(
    array $directions,
    array $lionCoords,
    array $elephantCoords
  ): array {
    $currentDistance = static::getDistance($lionCoords, $elephantCoords);
    $filtered = array_filter($directions, function ($direction) use ($elephantCoords, $currentDistance, $lionCoords) {
      $potentialLionCoords = ['x' => $lionCoords['x'] + $direction['x'], 'y' => $lionCoords['y'] + $direction['y']];
      return static::getDistance($potentialLionCoords, $elephantCoords) < $currentDistance;
    });
    return array_values($filtered);
  }

  public static function checkIfLionIsHereAndMove(int $x, int $y): void
  {
    $board = new Board();
    foreach (Meeples::getLions() as $lion) {
      $lionSquare = static::convertToSquareCoords(['x' => $lion->getX(), 'y' => $lion->getY()], false);
      if ($lionSquare['x'] === $x && $lionSquare['y'] === $y) {
        [$newX, $newY] = $board->getRandomSpaceNoneInSquare($lionSquare['x'], $lionSquare['y']);
        $lion->setX($newX);
        $lion->setY($newY);
        Notifications::lionMoved($lion);
        break;
      }
    }
  }

  public static function activate(array $lions, $cards = [], Player $activePlayer = null)
  {
    $elephantsEaten = [];
    $regularElephantsEatenNumber = 0;
    $isElephantsEaten = false;
    $isMatriarchInjured = false;
    /** @var Meeple $lion */
    foreach ($lions as $lion) {
      if ($lion->getState() === STATE_LAYING) {
        $lion->setState(STATE_STANDING);
      } else {
        $allElephants = [...Meeples::getElephantsOnBoard(), Meeples::getMatriarch()];
        $elephantsCoords = array_map(fn($elephant) => [
          'x' => $elephant->getX(),
          'y' => $elephant->getY()
        ], $allElephants);
        $board = new Board();
        $lionCoords = static::convertToSquareCoords(['x' => $lion->getX(), 'y' => $lion->getY()], false);
        $availableDirections = static::findAvailableDirections($lionCoords, $board);
        $closestElephantCoords = static::findClosest($lionCoords, $elephantsCoords);
        $potentialDirections = static::findDirectionsMakingLionCloser($availableDirections, $lionCoords, $closestElephantCoords);
        if (empty($potentialDirections)) {
          throw new \BgaVisibleSystemException("No directions found for lion at {$lionCoords['x']}, {$lionCoords['y']}");
        }
        // If PHP doesn't shuffle elements during array_values(), first direction should be a priority on the lion compass
        $dir = $potentialDirections[0];
        $squareX = $lionCoords['x'] + $dir['x'];
        $squareY = $lionCoords['y'] + $dir['y'];
        [$newX, $newY] = $board->getRandomSpaceNoneInSquare($squareX, $squareY);
        $lion->setX($newX);
        $lion->setY($newY);
        $elephantsEatenByThisLion = $board->getElephantsOfSquare($squareX, $squareY);
        $elephantsEaten = [...$elephantsEaten, ...$elephantsEatenByThisLion];
        $regularElephantsEatenNumber += count($elephantsEatenByThisLion);
        foreach ($elephantsEaten as $elephant) {
          $elephant->setLocation(LOCATION_DISCARD);
        }
        if (!empty($elephantsEatenByThisLion)) {
          $lion->setState(STATE_LAYING);
          $isElephantsEaten = true;
        }
        if ($board->isMatriarchInSquare($squareX, $squareY)) {
          if (!$isMatriarchInjured) {
            $isMatriarchInjured = true;
          }
          /** @var Player $player */
          foreach (Players::getAll() as $player) {
            $elephant = $player->eliminateRestedOrTiredElephant();
            if (!is_null($elephant)) {
              $elephantsEaten[] = $elephant;
            }
          }
        }
      };
    }
    if (!is_null($activePlayer)) {
      Notifications::lionsMoved($activePlayer, $lions, $cards);
    }
    if ($isElephantsEaten) {
      $msg = clienttranslate('${amount} Elephant(s) in an area with standing lions have been removed from the game');
      Notifications::message($msg, ['amount' => $regularElephantsEatenNumber]);
    }
    if ($isMatriarchInjured) {
      $msg = clienttranslate('A lion is chasing the Matriarch. Each player removes 1 elephant from the game');
      Notifications::message($msg);
      $lionsCoords = array_map(fn($lion) => ['x' => $lion->getX(), 'y' => $lion->getY()], $lions);
      if ($lionsCoords[0]['x'] === $lionsCoords[1]['x'] && $lionsCoords[0]['y'] === $lionsCoords[1]['y']) {
        Globals::setEndGame(true);
      }
    }
    if (!empty($elephantsEaten)) {
      Notifications::elephantsEaten($elephantsEaten);
    }

    return true;
  }
}
