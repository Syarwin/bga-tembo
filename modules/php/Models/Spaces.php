<?php

namespace Bga\Games\Tembo\Models;

/*
 * Class to handle spaces inside a 3x3 tile. We expect an array of 9 spaces, where 0 is the central space
 * and then, starting from the top-left corner, we go in spiral order.
 * So for any orientation we just need to shift the array by 2 * $orientation and center is always center
 */

class Spaces
{
  protected array $spaces = [];
  protected int $central;

  public function __construct(array $spaces)
  {
    $this->central = array_shift($spaces);
    $this->spaces = $spaces;
  }

  public function getByCoords(int $x, int $y, int $orientation): int
  {
    if ($x === 1 && $y === 1) {
      return $this->central;
    } else if ($x > 2 || $y > 2) {
      throw new \BgaVisibleSystemException("Incorrect space coords: $x, $y");
    } else {
      $spacesWithOffset = [];
      if ($orientation === 0) {
        $spacesWithOffset = $this->spaces;
      } else {
        for ($i = 0; $i <= 7; $i++) {
          $offset = $i - (2 * $orientation);
          if ($offset < 0) {
            $offset += 8;
          }
          $spacesWithOffset[] = $this->spaces[$offset];
        }
      }
      if ($y === 0) {
        return $spacesWithOffset[$x];
      }
      $mapping = [[0, 1, 2], [7, null, 3], [6, 5, 4]];
      return $spacesWithOffset[$mapping[$y][$x]];
    }
  }
}
