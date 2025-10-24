<?php

namespace Bga\Games\Tembo\Models;

use Bga\Games\Tembo\Core\Globals;

require_once dirname(__FILE__) . "/../Materials/Journeys.php";

const DIRECTIONS = [
  ['x' => 0, 'y' => 1],
  ['x' => 0, 'y' => -1],
  ['x' => 1, 'y' => 0],
  ['x' => -1, 'y' => 0],
];

class Board
{
  protected array $board = [];

  public static function setupNewGame()
  {
    $board = [];
    $boardTiles = ALL_BOARD_TILES;
    shuffle($boardTiles);
    $journey = JOURNEYS[Globals::getJourney()];
    for ($i = 0; $i < count($journey['tiles']); $i++) {
      $board[] = ['id' => $boardTiles[$i], 'rotation' => bga_rand(0, 3)];
    }
    Globals::setBoard($board);
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
  }

  public function getUiData(): array
  {
    return $this->board;
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
