<?php

namespace Bga\Games\Tembo\Actions;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Helpers\Collection;
use Bga\Games\Tembo\Helpers\Utils;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Meeples;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Action;
use Bga\Games\Tembo\Models\Board;
use Bga\Games\Tembo\Models\Meeple;
use Bga\Games\Tembo\Models\Player;

class ActivateLions extends Action
{
  const array DIRECTIONS = [ // Sorted following the lion compass image
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
    $board = new Board();

    $msg = clienttranslate('${player_name} gets a Lion card. All lions have been activated');
    Notifications::message($msg, ['player' => $activePlayer]);

    $lioness = Meeples::getLion(LIONESS);
    if (!is_null($lioness)) {
      static::moveLion($lioness, $board, $cards);
      static::chaseElephants($lioness, $board);
    }
    $lion = Meeples::getLion(LION);
    if (!is_null($lion)) {
      static::moveLion($lion, $board, $cards);
      static::chaseElephants($lion, $board);
    }
    return true;
  }

  private static function findAvailableDirections(array $lionCoords, Board $board)
  {
    $availableDirections = array_filter(static::getDirections(), function ($direction) use ($lionCoords, $board) {
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
    $target = Utils::convertToSquareCoords($target, false);

    foreach ($points as $point) {
      $point = Utils::convertToSquareCoords($point, false);
      if ($point === $target) {
        return $point;
      }

      $distance = static::getDistance($target, $point);

      if ($distance < $minDistance) {
        $minDistance = $distance;
        $closest = $point;
      } elseif ($distance === $minDistance) {
        // Use Lion compass
        $directions = static::getDirections();
        foreach ($directions as $direction) {
          $dx = $target['x'] + $direction['x'];
          $dy = $target['y'] + $direction['y'];
          if ($dx === $point['x'] && $dy === $point['y']) {
            $closest = $point;
            break;
          }
          if ($dx === $closest['x'] && $dy === $closest['y']) {
            break;
          }
        }
      }
    }

    return $closest;
  }

  public static function getSquareCorner($t)
  {
    return ['x' => $t['x'] - ($t['x'] % 3), 'y' => $t['y'] - ($t['y'] % 3)];
  }

  public static function getDistance($target, $point): int
  {
    $targetSquare = static::getSquareCorner($target);
    $sourceSquare = static::getSquareCorner($point);
    $board = new Board();

    $queue = [['square' => $sourceSquare, 'd' => 0]];
    $visited = [];
    while (!empty($queue)) {
      list('square' => $square, 'd' => $distance) = array_shift($queue);
      if (in_array($square, $visited)) continue;

      // Reached target
      if ($square == $targetSquare) return $distance;

      $visited[] = $square;
      // Check neighbours
      foreach (static::getDirections() as $delta) {
        $newSquare = ['x' => $square['x'] + $delta['x'], 'y' => $square['y'] + $delta['y']];
        if ($board->isSquareExist($newSquare) && !in_array($newSquare, $visited)) {
          $queue[] = ['square' => $newSquare, 'd' => $distance + 1];
        }
      }
    }

    $pointStr = implode(",", $point);
    $targetStr = implode(",", $target);
    throw new \BgaVisibleSystemException("Can't find any path from $pointStr to $targetStr");
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
      $lionSquare = Utils::convertToSquareCoords(['x' => $lion->getX(), 'y' => $lion->getY()], false);
      if ($lionSquare['x'] === $x && $lionSquare['y'] === $y) {
        [$newX, $newY] = $board->getRandomSpaceNoneInSquare($lionSquare['x'], $lionSquare['y']);
        $lion->setX($newX);
        $lion->setY($newY);
        Notifications::lionMoved($lion);
      }
    }
  }

  public static function moveLion(Meeple $lion, Board $board, ?object $cards = null): void
  {
    if ($lion->getState() === STATE_LAYING) {
      $lion->setState(STATE_STANDING);
    } else {
      $allElephants = [...Meeples::getElephantsOnBoard(), Meeples::getMatriarch()];
      $elephantsCoords = array_map(fn($elephant) => [
        'x' => $elephant->getX(),
        'y' => $elephant->getY()
      ], $allElephants);
      $lionCoords = Utils::convertToSquareCoords(['x' => $lion->getX(), 'y' => $lion->getY()], false);
      $availableDirections = static::findAvailableDirections($lionCoords, $board);
      $closestElephantCoords = static::findClosest($lionCoords, $elephantsCoords);
      $potentialDirections = static::findDirectionsMakingLionCloser($availableDirections, $lionCoords, $closestElephantCoords);
      if (empty($potentialDirections)) {
        $dir = ['x' => 0, 'y' => 0]; // Lion is already at the closest lion square
      } else {
        // If PHP doesn't shuffle elements during array_values(), first direction should be a priority on the lion compass
        $dir = $potentialDirections[0];
      }
      $squareX = $lionCoords['x'] + $dir['x'];
      $squareY = $lionCoords['y'] + $dir['y'];
      [$newX, $newY] = $board->getRandomSpaceNoneInSquare($squareX, $squareY);
      $lion->setX($newX);
      $lion->setY($newY);
    };
    Notifications::lionsMoved($lion, $cards);
  }

  public static function chaseElephants(
    Meeple $lion,
    Board $board,
  ): void {
    $elephantsEaten = [];
    $regularElephantsEatenNumber = 0;
    $isMatriarchInjured = false;

    $lionSquareCoords = Utils::convertToSquareCoords(['x' => $lion->getX(), 'y' => $lion->getY()], false);
    $elephantsEatenByThisLion = $board->getElephantsOfSquare($lionSquareCoords['x'], $lionSquareCoords['y']);
    $elephantsEaten = [...$elephantsEaten, ...$elephantsEatenByThisLion];
    $regularElephantsEatenNumber += count($elephantsEatenByThisLion);
    foreach ($elephantsEaten as $elephant) {
      $elephant->setLocation(LOCATION_DISCARD);
    }
    if ($board->isMatriarchInSquare($lionSquareCoords['x'], $lionSquareCoords['y'])) {
      $isMatriarchInjured = true;
      /** @var Player $player */
      foreach (Players::getAll() as $player) {
        $elephant = $player->eliminateRestedOrTiredElephant();
        if (!is_null($elephant)) {
          $elephantsEaten[] = $elephant;
        }
      }
    }
    if (!empty($elephantsEatenByThisLion) || $isMatriarchInjured) {
      $lion->setState(STATE_LAYING);
    }
    if (!empty($elephantsEatenByThisLion)) {
      $msg = clienttranslate('${amount} Elephant(s) in an area with standing lions have been removed from the game');
      Notifications::message($msg, ['amount' => $regularElephantsEatenNumber]);
    }
    if ($isMatriarchInjured) {
      $msg = clienttranslate('A lion is chasing the Matriarch. Each player removes 1 elephant from the game');
      Notifications::message($msg);
      $lionsCoords = array_map(fn($lion) => Utils::convertToSquareCoords([
        'x' => $lion->getX(),
        'y' => $lion->getY()
      ]), Meeples::getLions());

      if (count($lionsCoords) > 1 && $lionsCoords[0]['x'] === $lionsCoords[1]['x'] && $lionsCoords[0]['y'] === $lionsCoords[1]['y']) {
        Globals::setEndGame(true);
      }
    }
    if (!empty($elephantsEaten)) {
      Notifications::elephantsEaten($elephantsEaten);
      Notifications::lionsMoved($lion);
    }
  }

  private static function getDirections(): array
  {
    $directions = static::DIRECTIONS;
    for ($i = 0; $i < static::getCompassRotation(); $i++) {
      $first = array_shift($directions);
      $directions[] = $first;
    }
    return $directions;
  }

  public static function getCompassRotation(): int
  {
    return JOURNEYS[Globals::getJourney()]['start']['rotation'];
  }
}
