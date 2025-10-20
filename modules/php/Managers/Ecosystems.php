<?php

namespace Bga\Games\Tembo\Managers;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Models\Board;
use Bga\Games\Tembo\Models\Meeple;
use Bga\Games\Tembo\Models\Player;

class Ecosystems
{
  /**
   * @throws \BgaVisibleSystemException
   */
  public static function getScoresForAllEcosystems(Player $player): ?array
  {
    $ecosystemsInPlay = Globals::getEcosystems();
    $ecosystems = [];
    foreach ($ecosystemsInPlay as $cardId) {
      $ecosystems[$cardId] = static::getScoresForEcosystem($player, $cardId);
    }
    return $ecosystems;
  }

  /**
   * @throws \BgaVisibleSystemException
   */
  private static function getScoresForEcosystem(Player $player, int $cardId): int
  {
    $scores = 0;
    $playerBoard = $player->board();
    switch ($cardId) {
      case 1:
        return static::getScoresCompletedArea($playerBoard, FLOWER_RED);
      case 2:
        return static::getScoresCompletedArea($playerBoard, FLOWER_YELLOW);
      case 3:
        return static::getScoresCompletedArea($playerBoard, FLOWER_BLUE);
      case 4:
        return static::getScoresCompletedArea($playerBoard, FLOWER_GREY);
      case 5:
        return static::getScoresCompletedArea($playerBoard, FLOWER_WHITE);
      case 6:
        return static::getScoresTreesOnTopOf($player, FLOWER_RED);
      case 7:
        return static::getScoresTreesOnTopOf($player, FLOWER_YELLOW);
      case 8:
        return static::getScoresTreesOnTopOf($player, FLOWER_BLUE);
      case 9:
        return static::getScoresTreesOnTopOf($player, FLOWER_GREY);
      case 10:
        return static::getScoresTreesOnTopOf($player, FLOWER_WHITE);

      case 11: // Score 2 points for each Tree along the outer edges of your Rainforest boards
        $trees = $player->getTrees();
        /** @var Meeple $tree */
        foreach ($trees as $tree) {
          if (in_array($tree->getCoords(), static::getOuterEdges())) {
            $scores += 2;
          }
        }
        return $scores;

      case 12: // 6 points for each Animal along the outer edges of your Rainforest boards. Each Cassowary along such edges adds +4 points
        $animals = $player->getAnimals();
        /** @var Meeple $animal */
        foreach ($animals as $animal) {
          if (in_array($animal->getCoords(), static::getOuterEdges())) {
            $scores += 6;
            if ($animal->getType() === ANIMAL_CASSOWARY) {
              $scores += 4;
            }
          }
        }
        return $scores;

      case 13: // 2 points for each Animal you have in an Area of 2 spaces
        $cellsInAreasOfTwo = static::getCellsInAreasWithSize($player, 2);
        $animals = $player->getAnimals();
        foreach ($animals as $animal) {
          if (in_array($animal->getCoords(), $cellsInAreasOfTwo)) {
            $scores += 2;
          }
        }
        return $scores;

      case 14: // 7 points for each Animal you have in an Area of 4 or 5 spaces. Each Sumatran tiger in such Areas adds +4 points
        $areasOfFour = static::getCellsInAreasWithSize($player, 4);
        $areasOfFourOrFive = array_merge($areasOfFour, static::getCellsInAreasWithSize($player, 5));
        $animals = $player->getAnimals();
        foreach ($animals as $animal) {
          if (in_array($animal->getCoords(), $areasOfFourOrFive)) {
            $scores += 7;
            if ($animal->getType() === ANIMAL_TIGER) {
              $scores += 4;
            }
          }
        }
        return $scores;

      case 15: // 1 point for each Tree in your largest group of connected Trees (not diagonally)
        $groupsOfConnectedTrees = static::findTreeGroups($player->getTrees()->toArray());
        return empty($groupsOfConnectedTrees) ? 0 :
          max(array_map(fn($group) => count($group), $groupsOfConnectedTrees));

      case 16: // 5 points for each group of 3 or more connected Trees you have (not diagonally)
        $groupsOfConnectedTrees = static::findTreeGroups($player->getTrees()->toArray());
        $groupsOfThreeOrMore = array_filter($groupsOfConnectedTrees, function ($group) {
          return count($group) >= 3;
        });
        return count($groupsOfThreeOrMore) * 5;

      case 17: // For each lake, score 1 point for every adjacent Tree you have (also diagonally)
        $lakes = $playerBoard->getWaterSpaces();
        $trees = $player->getTrees();
        foreach ($lakes as $lake) {
          foreach ($trees as $tree) {
            if (static::isAdjacentIncludingDiagonals($lake, $tree->getCoords())) {
              $scores += 1;
            }
          }
        }
        return $scores;

      case 18: // For each lake, score 4 points for every adjacent Animal you have (also diagonally). Each Rhinoceros hornbill adds +2 points per adjacent lake
        $lakes = $playerBoard->getWaterSpaces();
        $animals = $player->getAnimals();
        foreach ($lakes as $lake) {
          foreach ($animals as $animal) {
            if (static::isAdjacentIncludingDiagonals($lake, $animal->getCoords())) {
              $scores += 4;
              if ($animal->getType() === ANIMAL_HORNBILL) {
                $scores += 2;
              }
            }
          }
        }
        return $scores;

      case 19: // For each Animal, score 2 points for every diagonally adjacent Tree you have. Each Sumatran rhino adds +2 points per diagonally adjacent Tree
        $animals = $player->getAnimals();
        $trees = $player->getTrees();
        foreach ($animals as $animal) {
          foreach ($trees as $tree) {
            if (static::isDiagonallyAdjacent($tree->getCoords(), $animal->getCoords())) {
              $scores += 2;
              if ($animal->getType() === ANIMAL_RHINOCEROS) {
                $scores += 2;
              }
            }
          }
        }
        return $scores;

      case 20: // 4 points for each different color Animal you have
        $animalsTypes = array_map(fn($animal) => $animal->getType(), $player->getAnimals()->toArray());
        return count(array_unique($animalsTypes)) * 4;

      case 21: // 4 points for each of your four Rainforest boards with an Animal on it
        $animals = $player->getAnimals()->toArray();
        $coords = array_map(fn($animal) => $animal->getCoords(), $animals);
        return static::getAmountOfBoardsHavingMeeples($coords) * 4;

      case 22: // Choose one of the five color Flowers. Score 4 points for each of your four Rainforest boards with at least one Area completed in that color
        $completedAreas = $playerBoard->getCompletedAreas();
        $boardsOfColor = [];
        foreach (ALL_COLORS as $color) {
          $areasOfColor = array_filter($completedAreas, function ($area) use ($color) {
            return $area['colors'][0] === $color;
          });
          // We need max 1 cell from each area as they never overlap more than 1 board
          $coords = array_map(fn($area) => $area['cells'][0], $areasOfColor);
          $boardsOfColor[$color] = static::getAmountOfBoardsHavingMeeples($coords);
        }
        $maxAmountOfAreas = max(array_values($boardsOfColor));
        return $maxAmountOfAreas * 4;

      case 23: // 8 points for each of your four Rainforest boards that has Trees/Animals/lakes on all spaces. Each Orangutan on such boards adds +4 points
        $boardsCount = 0;
        $orangutansCount = 0;
        foreach (static::getBoards() as $bounds) {
          $satisfies = true;
          $orangutansThisBoardCount = 0;
          for ($x = $bounds['minX']; $x <= $bounds['maxX']; $x++) {
            for ($y = $bounds['minY']; $y <= $bounds['maxY']; $y++) {
              if (!static::isCoordContainLakeAnimalTree($playerBoard, $x, $y)) {
                $satisfies = false;
                break;
              } else {
                $meepleTypesAtCell = array_map(fn($meeple) => $meeple->getType(), $playerBoard->getItemsAt($x, $y));
                if (in_array(ANIMAL_ORANGUTAN, $meepleTypesAtCell)) {
                  $orangutansThisBoardCount += 1;
                }
              }
            }
          }
          if ($satisfies) {
            $orangutansCount += $orangutansThisBoardCount;
            $boardsCount += 1;
          }
        }
        return $boardsCount * 8 + $orangutansCount * 4;

      case 24: // 6 points for each row and column that has Trees/Animals/lakes on all spaces
        $count = 0;

        // Rows
        for ($x = 0; $x < 6; $x++) {
          $satisfies = true;
          for ($y = 0; $y < 6; $y++) {
            if (!static::isCoordContainLakeAnimalTree($playerBoard, $x, $y)) {
              $satisfies = false;
              break;
            }
          }
          if ($satisfies) {
            $count += 1;
          }
        }

        // Columns
        for ($y = 0; $y < 6; $y++) {
          $satisfies = true;
          for ($x = 0; $x < 6; $x++) {
            if (!static::isCoordContainLakeAnimalTree($playerBoard, $x, $y)) {
              $satisfies = false;
              break;
            }
          }
          if ($satisfies) {
            $count += 1;
          }
        }

        return $count * 6;

      default:
        throw new \BgaVisibleSystemException("Unknown Ecosystem card $cardId");
    }
  }

  /**
   * @throws \BgaVisibleSystemException
   */
  private static function getScoresCompletedArea(Board $playerBoard, string $flowerColor): int
  {
    $result = 0;
    $completedAreas = $playerBoard->getCompletedAreas();
    foreach ($completedAreas as $area) {
      if ($area['colors'][0] === $flowerColor) {
        $result += 3;
      }
    }
    return $result;
  }

  /**
   * @throws \BgaVisibleSystemException
   */
  private static function getScoresTreesOnTopOf(Player $player, string $flowerColor): int
  {
    $result = 0;
    $trees = $player->getTrees();
    if ($trees->count() > 0) {
      foreach ($trees as $tree) {
        $treeCoords = $tree->getCoords();
        $x = $treeCoords['x'];
        $y = $treeCoords['y'];
        $itemsAtCoords = $player->board()->getItemsAt($x, $y);
        $flowersAtCoords = array_filter($itemsAtCoords, function ($meeple) {
          return in_array($meeple->getType(), ALL_COLORS);
        });
        if (count($flowersAtCoords) > 1) {
          throw new \BgaVisibleSystemException("Ecosystems: found more than 1 flower at coordinates $x, $y");
        } else if (count($flowersAtCoords) === 0) {
          throw new \BgaVisibleSystemException("Ecosystems: No flowers found under a tree at coordinates $x, $y");
        } else {
          if ($itemsAtCoords[0]->getType() === $flowerColor) {
            $result += 2;
          }
        }
      }
    }
    return $result;
  }

  private static function getOuterEdges(): array
  {
    $edges = [];
    for ($x = 0; $x < 6; $x++) {
      for ($y = 0; $y < 6; $y++) {
        if ($x === 0 || $x === 5 || $y === 0 || $y === 5) {
          $edges[] = ['x' => $x, 'y' => $y];
        }
      }
    }
    return $edges;
  }

  private static function getCellsInAreasWithSize(Player $player, int $size): array
  {
    $allAreas = $player->board()->getCompletedAreas();
    $areasOfSize = array_filter($allAreas, function ($area) use ($size) {
      return count($area['cells']) === $size;
    });
    return array_merge(...array_column($areasOfSize, 'cells'));
  }

  private static function findTreeGroups(array $trees): array
  {
    $treesCoords = array_map(fn($tree) => $tree->getCoords(), $trees);

    $treeMap = [];
    $visited = [];
    $groups = [];

    // Create a fast lookup map
    foreach ($treesCoords as $tree) {
      $key = $tree['x'] . ',' . $tree['y'];
      $treeMap[$key] = $tree;
    }

    // Directions: up, down, left, right
    $directions = [
      [0, 1],
      [0, -1],
      [1, 0],
      [-1, 0],
    ];

    // DFS function
    $dfs = function ($x, $y) use (&$dfs, &$treeMap, &$visited, $directions) {
      $stack = [[$x, $y]];
      $group = [];

      while ($stack) {
        [$cx, $cy] = array_pop($stack);
        $key = "$cx,$cy";

        if (isset($visited[$key]) || !isset($treeMap[$key])) {
          continue;
        }

        $visited[$key] = true;
        $group[] = ['x' => $cx, 'y' => $cy];

        foreach ($directions as [$dx, $dy]) {
          $nx = $cx + $dx;
          $ny = $cy + $dy;
          $neighborKey = "$nx,$ny";

          if (!isset($visited[$neighborKey]) && isset($treeMap[$neighborKey])) {
            $stack[] = [$nx, $ny];
          }
        }
      }

      return $group;
    };

    // Go through all trees
    foreach ($treesCoords as $tree) {
      $key = $tree['x'] . ',' . $tree['y'];
      if (!isset($visited[$key])) {
        $group = $dfs($tree['x'], $tree['y']);
        if ($group) {
          $groups[] = $group;
        }
      }
    }

    return $groups;
  }

  private static function isAdjacentIncludingDiagonals(array $firstObj, array $secondObj): bool
  {
    return abs($firstObj['x'] - $secondObj['x']) <= 1 &&
      abs($firstObj['y'] - $secondObj['y']) <= 1 &&
      !($firstObj['x'] === $secondObj['x'] && $firstObj['y'] === $secondObj['y']);
  }

  private static function isDiagonallyAdjacent($firstObj, $secondObj): bool
  {
    return abs($firstObj['x'] - $secondObj['x']) === 1 && abs($firstObj['y'] - $secondObj['y']) === 1;
  }

  private static function isCoordContainLakeAnimalTree(Board $playerBoard, int $x, int $y): bool
  {
    $meepleTypesAtCell = array_map(fn($meeple) => $meeple->getType(), $playerBoard->getItemsAt($x, $y));
    $hasAnimalOrTree = count(array_intersect($meepleTypesAtCell, [...ANIMALS, TREE])) > 0;
    return $hasAnimalOrTree || in_array(['x' => $x, 'y' => $y], $playerBoard->getWaterSpaces());
  }

  private static function getAmountOfBoardsHavingMeeples(array $meeplesCoords): int
  {
    $count = 0;
    foreach (static::getBoards() as $bounds) {
      foreach ($meeplesCoords as $meepleCoords) {
        $x = $meepleCoords['x'];
        $y = $meepleCoords['y'];
        if (
          $x >= $bounds['minX'] && $x <= $bounds['maxX'] &&
          $y >= $bounds['minY'] && $y <= $bounds['maxY']
        ) {
          $count += 1;
          break; // No need to check further
        }
      }
    }
    return $count;
  }

  private static function getBoards()
  {
    return [
      ['minX' => 0, 'maxX' => 2, 'minY' => 0, 'maxY' => 2],
      ['minX' => 0, 'maxX' => 2, 'minY' => 3, 'maxY' => 5],
      ['minX' => 3, 'maxX' => 5, 'minY' => 0, 'maxY' => 2],
      ['minX' => 3, 'maxX' => 5, 'minY' => 3, 'maxY' => 5],
    ];
  }
}
