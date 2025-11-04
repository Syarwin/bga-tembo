<?php

namespace Bga\Games\Tembo\Models;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Managers\Cards;
use Bga\Games\Tembo\Managers\Meeples;

require_once dirname(__FILE__) . "/../Materials/Journeys.php";
require_once dirname(__FILE__) . "/../Materials/BoardTiles.php";

const DIRECTIONS = [
  ['x' => 0, 'y' => 1],
  ['x' => 0, 'y' => -1],
  ['x' => 1, 'y' => 0],
  ['x' => -1, 'y' => 0],
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
    $cells = SHAPES_CELLS[$shape];
    foreach ($cells as $delta) {
      if (($rotation % 2) == 0) {
        $dx = $rotation == 0 ? $delta[0] : -$delta[0];
        $dy = $rotation == 0 ? $delta[1] : -$delta[1];
      } else {
        $dx = $rotation == 1 ? -$delta[1] : $delta[1];
        $dy = $rotation == 1 ? $delta[0] : -$delta[0];
      }

      $nx = $x + $dx;
      $ny = $y + $dy;
      $cellType = $this->cells[$nx][$ny] ?? null;
      if (is_null($cellType) || $cellType == SPACE_NONE) {
        return INFINITY;
      }
      $elephantsNeeded += ($cellType == SPACE_ROUGH && !$ignoreRough) ? 2 : 1;
    }

    return $elephantsNeeded;
  }

  public function canFitShape(int $shape, int $x, int $y, int $rotation, bool $ignoreRough, int $nElephantAvailable): bool
  {
    $elephantsNeeded = $this->getFitShapeElephantCost($shape, $x, $y, $rotation, $ignoreRough);
    return $nElephantAvailable >= $elephantsNeeded;
  }
}
