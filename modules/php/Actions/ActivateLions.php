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
    $player = Players::getActive();
    $cards = $player->getLionCards();
    Cards::move($cards->getIds(), LOCATION_DISCARD);
    $lions = Meeples::getLions();
    $elephantsEaten = [];
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
        $lionCoords = ['x' => $lion->getX(), 'y' => $lion->getY()];
        $availableDirections = $this->findAvailableDirections($lionCoords, $board);
        $closestElephantCoords = $this->findClosest($lionCoords, $elephantsCoords);
        $potentialDirections = $this->findDirectionsMakingLionCloser($availableDirections, $lionCoords, $closestElephantCoords);
        if (empty($potentialDirections)) {
          throw new \BgaVisibleSystemException("No directions found for lion at {$lionCoords['x']}, {$lionCoords['y']}");
        }
        // If PHP doesn't shuffle elements during array_values(), first direction should be a priority on the lion compass
        $direction = $potentialDirections[0];
        $newX = $lionCoords['x'] + $direction['x'];
        $newY = $lionCoords['y'] + $direction['y'];
        $lion->setX($newX);
        $lion->setY($newY);
        $elephantsEatenByThisLion = $board->getElephantsOfSquare($newX, $newY);
        $elephantsEaten = [...$elephantsEaten, ...$elephantsEatenByThisLion];
        foreach ($elephantsEaten as $elephant) {
          $elephant->setLocation(LOCATION_DISCARD);
        }
        if (!empty($elephantsEatenByThisLion)) {
          $lion->setState(STATE_LAYING);
        }
        if ($board->isMatriarchInSquare($newX, $newY)) {
          if (!$isMatriarchInjured) {
            $isMatriarchInjured = true;
          }
          /** @var Player $player */
          foreach (Players::getAll() as $player) {
            if ($player->getRestedElephantsAmount() > 0) {
              $player->eliminateRestedElephant();
            } else {
              $player->eliminateTiredElephant();
            }
          }
        }
      };
    }
    Notifications::lionsMoved($player, $lions, $cards);
    if (!empty($elephantsEaten)) {
      Notifications::elephantsEaten($elephantsEaten);
    }
    if ($isMatriarchInjured) {
      $playersElephants = [];
      foreach (Players::getAll() as $player) {
        $playersElephants[$player->getId()]['rested'] = $player->getRestedElephantsAmount();
        $playersElephants[$player->getId()]['tired'] = $player->getTiredElephantsAmount();
      }
      Notifications::matriarchInjured($playersElephants);
      $lionsCoords = array_map(fn($lion) => ['x' => $lion->getX(), 'y' => $lion->getY()], $lions);
      if ($lionsCoords[0]['x'] === $lionsCoords[1]['x'] && $lionsCoords[0]['y'] === $lionsCoords[1]['y']) {
        Globals::setEndGame(true);
      }
    }

    return true;
  }

  private function findAvailableDirections(array $lionCoords, Board $board)
  {
    $availableDirections = array_filter(self::DIRECTIONS, function ($direction) use ($lionCoords, $board) {
      $coords = ['x' => $lionCoords['x'] + $direction['x'], 'y' => $lionCoords['y'] + $direction['y']];
      return $board->isSquareExist($coords);
    });
    return array_values($availableDirections);
  }

  private function findClosest($target, $points): array
  {
    $closest = null;
    $minDistance = PHP_FLOAT_MAX;
    // Convert real coords to "square" coords (top-left corner of each square)
    $target = $this->convertToSquareCoords($target);

    foreach ($points as $point) {
      $point = $this->convertToSquareCoords($point);
      if ($point === $target) {
        return $point;
      }

      $distance = $this->getDistance($target, $point);

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

  private function convertToSquareCoords($cell)
  {
    $cell['x'] = ($cell['x'] - ($cell['x'] % 3)) / 3;
    $cell['y'] = ($cell['y'] - ($cell['y'] % 3)) / 3;
    return $cell;
  }

  private function getDistance($target, $point): int
  {
    return abs($point['x'] - $target['x']) + abs($point['y'] - $target['y']);
  }

  private function findDirectionsMakingLionCloser(array $directions, array $lionCoords, array $elephantCoords): array
  {
    $currentDistance = $this->getDistance($lionCoords, $elephantCoords);
    $filtered = array_filter($directions, function ($direction) use ($elephantCoords, $currentDistance, $lionCoords) {
      $potentialLionCoords = ['x' => $lionCoords['x'] + $direction['x'], 'y' => $lionCoords['y'] + $direction['y']];
      return $this->getDistance($potentialLionCoords, $elephantCoords) < $currentDistance;
    });
    return array_values($filtered);
  }
}
