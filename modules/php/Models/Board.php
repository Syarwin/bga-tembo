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
          // Landmark square type
          if (in_array($type, ALL_CARD_REFS)) {
            $spaces = new Spaces(LANDMARK_ZONES[$type]);
            $rotation = $tile['rotation'];
          }
          // Savanna card placed on that square?
          $card = Cards::getAtSquare($x, $y);
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

          $this->squares[] = [
            'type' => $b[$j][$i],
            'x' => $x,
            'y' => $y,
          ];
        }
      }
    }
  }

  public function getUiData(): array
  {
    return [
      'tiles' => $this->board,
      'lions' => Meeples::getLions(),
      'trees' => Meeples::getTrees(),
      'landmarks' => Meeples::getLandmarks(),

      // Used for debugging
      'squares' => $this->squares,
      'cells' => $this->cells,
    ];
  }

  public function getJourney1FromRulebook(): array
  {
    return [
      'start' => ['x' => 12, 'y' => 0, 'rotation' => 0],
      'destination' => ['x' => 0, 'y' => 24, 'rotation' => 0],
      'tiles' => [
        [
          'id' => BOARD_TILE_DIAGONAL_CANYON,
          'x' => 12,
          'y' => 3,
          'rotation' => 1,
        ],
        [
          'id' => BOARD_TILE_L_SHAPED_RIVER,
          'x' => 12,
          'y' => 9,
          'rotation' => 0,
        ],
        [
          'id' => BOARD_TILE_DIAGONAL_MEADOW,
          'x' => 6,
          'y' => 12,
          'rotation' => 2,
        ],
        [
          'id' => BOARD_TILE_CORNER_WATERFALL,
          'x' => 12,
          'y' => 15,
          'rotation' => 3,
        ],
        [
          'id' => BOARD_TILE_V_ROCKS,
          'x' => 0,
          'y' => 18,
          'rotation' => 0,
        ],
        [
          'id' => BOARD_TILE_SINGLE_SNOW,
          'x' => 6,
          'y' => 18,
          'rotation' => 1,
        ],
      ],
    ];
  }
}
