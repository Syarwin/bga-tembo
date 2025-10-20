<?php

namespace Bga\Games\Tembo\Models;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Helpers\Utils;
use Bga\Games\Tembo\Managers\Meeples;

const DIRECTIONS = [
  ['x' => 0, 'y' => 1],
  ['x' => 0, 'y' => -1],
  ['x' => 1, 'y' => 0],
  ['x' => -1, 'y' => 0],
];

const COLOR_ANIMAL_MAP = [
  FLOWER_BLUE => ANIMAL_CASSOWARY,
  FLOWER_YELLOW => ANIMAL_TIGER,
  FLOWER_RED => ANIMAL_ORANGUTAN,
  FLOWER_WHITE => ANIMAL_RHINOCEROS,
  FLOWER_GREY => ANIMAL_HORNBILL
];

class Board
{
  protected ?Player $player;
  protected array $cells;
  protected array $cellsZone;
  protected array $zones;
  private array $waterSpaces;

  public function __construct(?Player $player)
  {
    $this->player = $player;

    // Compute zones according to boards
    $boards = Globals::getBoards();
    foreach ($boards as $k => [$boardId, $orientation]) {
      $board = BOARDS[$boardId];

      // For each of the 3x3 cell
      foreach ($board as $i => $row) {
        foreach ($row as $j => $zone) {
          // Rotate the cell depending on the orientation
          switch ($orientation) {
            case NW:
              $x = $i;
              $y = $j;
              break;

            case NE:
              $x = $j;
              $y = 2 - $i;
              break;

            case SE:
              $x = 2 - $i;
              $y = 2 - $j;
              break;

            case SW:
              $x = 2 - $j;
              $y = $i;
              break;
          }

          // Add delta depending on index $k
          if ($k == 1 || $k == 2) {
            $y += 3;
          }
          if ($k == 2 || $k == 3) {
            $x += 3;
          }

          // Unique zone id (each board has at most 4 zones)
          if ($zone != WATER) {
            $zoneId = $zone;
            $this->zones[$zoneId]['cells'][] = ['x' => $x, 'y' => $y];
            $this->cellsZone[$x][$y] = $zoneId;
          } else {
            $this->waterSpaces[] = ['x' => $x, 'y' => $y];
            $this->cellsZone[$x][$y] = WATER;
          }
        }
      }
    }

    if (!is_null($this->player)) {
      $this->refresh();
    }
  }

  public function getWaterSpaces()
  {
    return $this->waterSpaces;
  }

  public function getZones(): array
  {
    return $this->zones;
  }

  /**
   * @throws \BgaVisibleSystemException
   */
  public function getCompletedAreas(): array
  {
    return array_filter($this->getCompletedAndMixedAreas(), function ($area) {
      return count($area['colors']) === 1;
    });
  }

  /**
   * @throws \BgaVisibleSystemException
   */
  public function getCompletedAndMixedAreas($includeColors = true): array
  {
    return $this->getZonesWithMeeplesOnEachCell(1, false, $includeColors);
  }

  /**
   * @throws \BgaVisibleSystemException
   */
  public function getFullyFilledZones(bool $animalsOnly = false): array
  {
    return $this->getZonesWithMeeplesOnEachCell(2, $animalsOnly);
  }

  /**
   * @throws \BgaVisibleSystemException
   */
  private function getZonesWithMeeplesOnEachCell(int $threshold, bool $withAnimalsOnly = false): array
  {
    $zones = array_filter($this->zones, function ($zone) use ($threshold, $withAnimalsOnly) {
      $correctZone = true;
      $animalsFound = false;
      foreach ($zone['cells'] as $cell) {
        $meeplesAtCell = $this->getItemsAt($cell['x'], $cell['y']);
        if (count($meeplesAtCell) < $threshold) {
          $correctZone = false;
        }
        if ($correctZone && $withAnimalsOnly && !$animalsFound) {
          $meepleTypes = array_map(fn(Meeple $meeple) => $meeple->getType(), $meeplesAtCell);
          $animalsFound = !empty(array_intersect($meepleTypes, ANIMALS));
        }
      }
      return $correctZone && ($animalsFound || !$withAnimalsOnly);
    });
    foreach ($zones as &$zone) {
      $zone['colors'] = Utils::getFlowerColorsForZone($this, $zone);
    }
    return $zones;
  }

  /**
   * @throws \BgaVisibleSystemException
   */
  public function getZonesWithMeeples(): array
  {
    $zones = array_filter($this->zones, function ($zone) {
      $hasMeeples = false;
      foreach ($zone['cells'] as $cell) {
        if (count($this->getItemsAt($cell['x'], $cell['y'])) > 0) {
          $hasMeeples = true;
        }
      }
      return $hasMeeples;
    });
    foreach ($zones as &$zone) {
      $zone['colors'] = Utils::getFlowerColorsForZone($this, $zone);
    }
    return $zones;
  }

  public function getCellsZone(): array
  {
    return $this->cellsZone;
  }

  public function refresh()
  {
    // Empty grid
    for ($x = 0; $x < 6; $x++) {
      for ($y = 0; $y < 6; $y++) {
        $this->cells[$x][$y] = [];
      }
    }

    // Add meeples
    /** @var Meeple $meeple */
    foreach ($this->player->getMeeples() as $meeple) {
      $this->cells[$meeple->getX()][$meeple->getY()][] = $meeple;
    }
  }

  public function addFlower(int $x, int $y, string $color): Meeple
  {
    $isTree = !$this->isEmpty($x, $y);
    $flowerType = $isTree ? TREE : $color;
    $meeple = Meeples::place($this->player->getId(), $x, $y, $flowerType);
    $this->cells[$x][$y][] = $meeple;
    return $meeple;
  }

  public function placeAnimal(int $x, int $y): array
  {
    $treeToRemove = array_pop($this->cells[$x][$y]);
    if (is_null($treeToRemove) || $treeToRemove->getType() != TREE) {
      throw new \BgaVisibleSystemException('Cant place an animal here');
    }

    $color = $this->cells[$x][$y][0]->getType();
    $animalType = COLOR_ANIMAL_MAP[$color];
    $animal = Meeples::getNextAvailableAnimal($animalType);
    if (!$animal) {
      throw new \BgaVisibleSystemException(
        "Unable to place an animal: There's no more animals of this type. This should not happen"
      );
    }
    $animal->setX($x);
    $animal->setY($y);
    $animal->setPId($this->player->getId());
    $animal->setLocation(LOCATION_TABLE);
    $this->cells[$x][$y][] = $animal;
    return [$treeToRemove, $animal];
  }

  public function isEmpty(int $x, int $y): bool
  {
    return empty($this->cells[$x][$y]);
  }

  public function getItemsAt(int $x, int $y): ?array
  {
    return $this->cells[$x][$y] ?? null;
  }

  public function getPlacableColorsAtCell(int $x, int $y, ?array $availableColors = null): array
  {
    // Cant place flower on water
    if ($this->cellsZone[$x][$y] == WATER) return [];

    $availableColors = $availableColors ?? ALL_COLORS;
    $meeples = $this->getItemsAt($x, $y);

    // Full cell => nothing can be placed here
    if (count($meeples) == 2) {
      return [];
    }
    // Empty cell => can place any clors
    if (empty($meeples)) {
      return $availableColors;
    }

    // A flower here => only same color
    $color = $meeples[0]->getType();
    return in_array($color, $availableColors) ? [$color] : [];
  }

  protected function canPlayCardAtCellAux(int $x, int $y, array $colorsToPlace, array $visited)
  {
    // Already visited => can't put another flower here
    if (in_array("{$x}_{$y}", $visited)) {
      return false;
    }

    // Can we place at least one of the flowers here?
    $colors = $this->getPlacableColorsAtCell($x, $y);
    if (empty($colors)) {
      return false;
    }

    // For each possible color
    $visited[] = "{$x}_{$y}";
    foreach ($colors as $color) {
      $key = array_search($color, $colorsToPlace);
      if ($key === false) continue;

      unset($colorsToPlace[$key]);

      // We placed all the colors!
      if (empty($colorsToPlace)) {
        return true;
      }

      // Otherwise check neighbour to place the other ones
      foreach (DIRECTIONS as $dir) {
        $nx = $x + $dir['x'];
        $ny = $y + $dir['y'];
        if ($nx < 0 || $nx > 5 || $ny < 0 || $ny > 5) continue;

        if ($this->canPlayCardAtCellAux($nx, $ny, $colorsToPlace, $visited)) {
          return true;
        }
      }

      // No success with this color, put in back into the array
      $colorsToPlace[$key] = $color;
    }

    // If we are here, we haven't found any possibility
    return false;
  }

  public function canPlayCard(FlowerCard $card): bool
  {
    $colors = $card->getFlowers();
    if (count($colors) == 1) {
      return true; // It's impossible to completely fill the board so single joker can always be placed
    }

    for ($x = 0; $x < 6; $x++) {
      for ($y = 0; $y < 6; $y++) {
        if ($this->canPlayCardAtCellAux($x, $y, $colors, [])) {
          return true;
        }
      }
    }

    return false;
  }

  public function getAmountOfMeeples(): int
  {
    $amount = 0;
    for ($x = 0; $x < 6; $x++) {
      for ($y = 0; $y < 6; $y++) {
        $amount = $amount + count($this->cells[$x][$y]);
      }
    }
    return $amount;
  }

  public function moveTreeToReserve(Meeple $tree)
  {
    Meeples::delete($tree->getId());
  }
}
