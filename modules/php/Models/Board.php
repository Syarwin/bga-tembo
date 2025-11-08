<?php

namespace Bga\Games\Tembo\Models;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Helpers\Collection;
use Bga\Games\Tembo\Helpers\Utils;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Meeples;

require_once dirname(__FILE__) . "/../Materials/Journeys.php";
require_once dirname(__FILE__) . "/../Materials/BoardTiles.php";

const DIRECTIONS = [
  ['x' => -1, 'y' => -1],
  ['x' => 0, 'y' => -1],
  ['x' => 1, 'y' => -1],
  ['x' => -1, 'y' => 0],
  ['x' => 1, 'y' => 0],
  ['x' => -1, 'y' => 1],
  ['x' => 0, 'y' => 1],
  ['x' => 1, 'y' => 1],
];

const LANDMARK_SPACES = [
  CARD_REF_SINGLE_SNOW => 1,
  CARD_REF_DIAGONAL_MEADOW => 2,
  CARD_REF_L_SHAPED_RIVER => 4,
  CARD_REF_V_ROCKS => 3,
  CARD_REF_DIAGONAL_CANYON => 3,
  CARD_REF_CORNER_WATERFALL => 3,
];

class Board
{
  protected array $board = [];
  protected array $squares = [];
  protected array $cells = [];

  public static function setupNewGame(int $journey): array
  {
    $board = [];
    $boardTiles = ALL_BOARD_TILES;
    shuffle($boardTiles);
    $journey = JOURNEYS[$journey];
    for ($i = 0; $i < count($journey['tiles']); $i++) {
      $board[] = ['id' => $boardTiles[$i], 'rotation' => bga_rand(0, 3)];
    }
    Globals::setBoard($board);
    return $board;
  }

  public function __construct()
  {
    $journey = JOURNEYS[Globals::getJourney()];
    $this->board = [
      'start' => $journey['start'],
      'destination' => $journey['destination'],
      'tiles' => [],
    ];

    $board = Globals::getBoard();
    foreach ($board as $index => $tile) {
      $this->board['tiles'][] = [...$journey['tiles'][$index], ...$tile];
    }

    foreach ($this->board['tiles'] as $tile) {
      // Squares of that tile
      $b = BOARD_TILES[$tile['id']];
      $b = [
        [$b[0], $b[1]],
        [$b[2], $b[3]]
      ];
      for ($r = 0; $r < $tile['rotation']; $r++) {
        $b = [
          [$b[1][0], $b[0][0]],
          [$b[1][1], $b[0][1]]
        ];
      }

      // Compute correesponding coordinates
      for ($i = 0; $i < 2; $i++) {
        for ($j = 0; $j < 2; $j++) {
          $x = $tile['x'] + 3 * $i;
          $y = $tile['y'] + 3 * $j;
          $type = $b[$j][$i];

          $spaces = null;
          $rotation = null;
          $square = [
            'type' => $b[$j][$i],
            'x' => $x,
            'y' => $y,
          ];
          if ($this->isSquareHasLandmark($square)) {
            $spaces = new Spaces(LANDMARK_ZONES[$type]);
            $rotation = $tile['rotation'];
          }
          $card = $this->getSquareCard($square);
          if (!is_null($card)) {
            $spaces = $card->getSpaces();
            $rotation = $card->getRotation();
          }

          // Cells informations
          if (!is_null($spaces)) {
            for ($dx = 0; $dx < 3; $dx++) {
              for ($dy = 0; $dy < 3; $dy++) {
                $this->cells[$x + $dx][$y + $dy] = $spaces->getByCoords($dx, $dy, $rotation);
              }
            }
          }

          $this->squares[] = $square;
        }
      }
    }
  }

  private function isSquareHasLandmark(array $square): bool
  {
    return in_array($square['type'], ALL_CARD_REFS);
  }

  private function getSquareCard(array $square): ?Card
  {
    return Cards::getAtSquare($square['x'], $square['y']);
  }

  public function getEmptySquares(): array
  {
    $squares = [];
    foreach ($this->squares as $square) {
      if (!$this->isSquareHasLandmark($square) && is_null($this->getSquareCard($square))) {
        $squares[] = $square;
      }
    }
    return $squares;
  }

  public function getUiData(): array
  {
    return [
      'tiles' => $this->board,

      // Used for debugging
      'squares' => $this->squares,
      'cells' => $this->cells,
    ];
  }

  public function getFitShapeElephantCost(int $shape, int $x, int $y, int $rotation, bool $ignoreRough): int
  {
    $elephantsNeeded = 0;
    foreach ($this->getCellTypesForShape($shape, $x, $y, $rotation) as $cellType) {
      if (is_null($cellType) || $cellType == SPACE_NONE) {
        return INFINITY;
      }
      $elephantsNeeded += ($cellType == SPACE_ROUGH && !$ignoreRough) ? 2 : 1;
    }

    return $elephantsNeeded;
  }

  private function getCellTypesForShape(int $shape, int $x, int $y, int $rotation): array
  {
    return array_map(fn($cell) => $this->cells[$cell['x']][$cell['y']] ?? null, $this->getCellsForShape($shape, $x, $y, $rotation));
  }

  public function getCellsForShape(int $shape, int $x, int $y, int $rotation)
  {
    $cells = [];
    $cellsOfShape = SHAPES_CELLS[$shape];
    foreach ($cellsOfShape as $delta) {
      if (($rotation % 2) == 0) {
        $dx = $rotation == 0 ? $delta[0] : -$delta[0];
        $dy = $rotation == 0 ? $delta[1] : -$delta[1];
      } else {
        $dx = $rotation == 1 ? -$delta[1] : $delta[1];
        $dy = $rotation == 1 ? $delta[0] : -$delta[0];
      }

      $nx = $x + $dx;
      $ny = $y + $dy;
      $cells[] = ['x' => $nx, 'y' => $ny];
    }
    return $cells;
  }

  public function canFitShape(int $shape, int $x, int $y, int $rotation, int $nElephantAvailable,
    bool $ignoreRough = false): bool
  {
    $elephantsNeeded = $this->getFitShapeElephantCost($shape, $x, $y, $rotation, $ignoreRough);

    $noNoneSpaces = !in_array(SPACE_NONE, $this->getCellTypesForShape($shape, $x, $y, $rotation));

    $cellsOfShape = $this->getCellsForShape($shape, $x, $y, $rotation);
    $meeplesOnCellsMap = array_map(fn($cell) => Meeples::getOnCell($cell)->empty(), $cellsOfShape);
    $noMeeplesOnCellsMap = !in_array(false, array_unique($meeplesOnCellsMap));

    return $nElephantAvailable >= $elephantsNeeded && $noNoneSpaces && $noMeeplesOnCellsMap;
  }

  public function getAllPossibleCoordsSingle(bool $ignoreRough = false): array
  {
    $matriarch = Meeples::getMatriarch();
    [$x, $y] = [$matriarch->getX(), $matriarch->getY()];
    if ($matriarch->getLocation() === 'board-start') {
      [$x, $y] = [$this->board['start']['x'] + 1, $this->board['start']['y']];
    }
    $allCoords = $this->getAdjacentCoordsSingle($x, $y, $ignoreRough);
    foreach (Meeples::getElephantsOnBoard() as $elephant) {
      foreach ($this->getAdjacentCoordsSingle($elephant->getX(), $elephant->getY(), $ignoreRough) as $coords) {
        if (!in_array($coords, $allCoords)) {
          $allCoords[] = [...$coords, 'amount' => 1];
        }
      }
    }
    return $allCoords;
  }

  private function getAdjacentCoordsSingle(int $x, int $y, bool $ignoreRough): array
  {
    $results = [];
    foreach (DIRECTIONS as $direction) {
      $dx = $x + $direction['x'];
      $dy = $y + $direction['y'];
      $cellType = $this->cells[$dx][$dy] ?? null;

      $meeplesAtSpace = Meeples::getOnCell(['x' => $dx, 'y' => $dy]);
      if (!is_null($cellType) && $cellType !== SPACE_NONE && $meeplesAtSpace->empty()) {
        if ($ignoreRough || $cellType !== SPACE_ROUGH) {
          $results[] = ['x' => $dx, 'y' => $dy];
        }
      }
    }
    return $results;
  }

  public function getAllPossiblePatterns(Collection $hand, int $rotation, int $nElephantsAvailable): array
  {
    $patterns = [];
    $adjacentSpaces = $this->getAllPossibleCoordsSingle(true);
    /** @var Card $card */
    foreach ($hand as $card) {
      $patternInfo = $card->getPattern();
      $rotations = [$rotation];
      if ($patternInfo['canBeRotated']) {
        $rotations = [0, 1, 2, 3];
      }
      // TODO: Find max width and height
      for ($x = 0; $x < 30; $x++) {
        for ($y = 0; $y < 30; $y++) {
          foreach ($rotations as $rotation) {
            if ($this->canFitShape($patternInfo['shape'], $x, $y, $rotation, $nElephantsAvailable, $patternInfo['ignoreRough'])) {
              $cellsForThisShape = $this->getCellsForShape($patternInfo['shape'], $x, $y, $rotation);
              if (Utils::someCellsIntersect($cellsForThisShape, $adjacentSpaces)) {
                if (!isset($patterns[$card->getId()])) {
                  $patterns[$card->getId()] = [];
                }
                // Inject amount of elephants needed for each cell
                $cellsForThisShape = array_map(function ($cell) {
                  $cellType = $this->cells[$cell['x']][$cell['y']];
                  return [...$cell, 'amount' => $cellType === SPACE_ROUGH ? 2 : 1];
                }, $cellsForThisShape);
                $patterns[$card->getId()] = array_merge($patterns[$card->getId()], [$cellsForThisShape]);
              }
            }
          }
        }
      }
    }
    return $patterns;
  }

  public function injectSpacesTypes(array $pattern)
  {
    return array_map(fn($cell) => [...$cell, 'type' => $this->cells[$cell['x']][$cell['y']]], $pattern);
  }

  public function getCorrespondingTreeSpace(array $cell): array
  {
    $x = $cell['x'];
    $y = $cell['y'];
    // First find the square this cell belongs to
    $squareCoords = ['x' => $x - $x % 3, 'y' => $y - $y % 3];
    foreach (DIRECTIONS as $direction) {
      $dx = $x + $direction['x'];
      $dy = $y + $direction['y'];
      // The tree space should be inside this square
      if ($dx >= $squareCoords['x'] && $dx < $squareCoords['x'] + 3 && $dy >= $squareCoords['y'] && $dy < $squareCoords['y'] + 3) {
        if ($this->cells[$dx][$dy] === $cell['type']) {
          return ['x' => $dx, 'y' => $dy];
        }
      }
    }
    throw new \BgaVisibleSystemException("Cannot find a corresponding tree to cell x: $x, y: $y");
  }

  public function getCorrespondingLandmarkSpaces(array $cell): array
  {
    $landmarkCells = [];
    $cellX = $cell['x'];
    $cellY = $cell['y'];
    $squareCoords = ['x' => $cellX - $cellX % 3, 'y' => $cellY - $cellY % 3];
    $landmarkSpaces = LANDMARK_SPACES[$this->getLandmarkByCell($cell)];
    if ($landmarkSpaces < 2) {
      return [];
    }
    for ($x = $squareCoords['x']; $x < $squareCoords['x'] + 3; $x++) {
      for ($y = $squareCoords['y']; $y < $squareCoords['y'] + 3; $y++) {
        if ($this->cells[$x][$y] === SPACE_LANDMARK && !($x === $cellX && $y === $cellY)) {
          $landmarkCells[] = ['x' => $x, 'y' => $y];
        }
      }
    }
    if (count($landmarkCells) < $landmarkSpaces - 1) {
      throw new \BgaVisibleSystemException("Not enough landmark cells for landmark x: $cellX, y: $cellY");
    }
    return $landmarkCells;
  }

  public function getLandmarkByCell(array $cell): int
  {
    $x = $cell['x'];
    $y = $cell['y'];
    $squareCoords = ['x' => $x - $x % 3, 'y' => $y - $y % 3];
    $square = array_filter($this->squares, fn($square) => $square['x'] === $squareCoords['x'] && $square['y'] === $squareCoords['y']);
    return array_values($square)[0]['type'];
  }
}