<?php

namespace Bga\Games\Tembo\Models;

const DIRECTIONS = [
  ['x' => 0, 'y' => 1],
  ['x' => 0, 'y' => -1],
  ['x' => 1, 'y' => 0],
  ['x' => -1, 'y' => 0],
];

class Board
{
  public function __construct()
  {
  }

  public function getUiFakeData(): array
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
