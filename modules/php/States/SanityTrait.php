<?php

namespace Bga\Games\Tembo\States;

use Bga\Games\Tembo\Helpers\Utils;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Models\Meeple;
use Bga\Games\Tembo\Models\Player;

trait SanityTrait
{
  /**
   * @throws \BgaVisibleSystemException
   */
  public function verifyTurnParams(array $flowers, array $cardFlowers)
  {
    $flowersCount = count($flowers);
    $cardFlowersCount = count($cardFlowers);
    if ($flowersCount !== $cardFlowersCount) {
      throw new \BgaVisibleSystemException(
        "Incorrect amount of flowers: expected $cardFlowersCount, actual $flowersCount"
      );
    }

    foreach ($flowers as $i => $flower) {
      $color = $flower['color'];
      $cardFlowerColor = $cardFlowers[$i];
      if ($color !== $cardFlowers[$i]) {
        throw new \BgaVisibleSystemException(
          "Incorrect flowers received: expected $cardFlowerColor, actual $color at index $i"
        );
      }
    }

    $coords = array_map(fn($flower) => $flower['x'] . "," . $flower['y'], $flowers);
    if (count($coords) !== count(array_unique($coords))) {
      throw new \BgaVisibleSystemException("You cannot place two flowers at the same position in a single turn");
    }

    // Each flower should have either x+-1 from one another or y+-1
    if (count($flowers) > 1) {
      foreach ($flowers as $flower) {
        $x = $flower['x'];
        $y = $flower['y'];
        $adjacentFlowers = array_filter($flowers, function ($flower) use ($x, $y) {
          return abs($flower['x'] - $x) + abs($flower['y'] - $y) === 1;
        });
        if (count($adjacentFlowers) === 0) {
          throw new \BgaVisibleSystemException(
            "You cannot place flowers which are not adjacent orthogonally to any other flower from this card"
          );
        }
      }
    }

    $playerBoard = Players::getActive()->board();
    foreach ($flowers as $flower) {
      $x = $flower['x'];
      $y = $flower['y'];
      $itemsAtCoords = $playerBoard->getItemsAt($x, $y);
      if (count($itemsAtCoords) > 1) {
        throw new \BgaVisibleSystemException(
          "You cannot place a flower at $x, $y, it's already fully occupied"
        );
      } else if (count($itemsAtCoords) === 1) {
        /** @var Meeple $flowerAtCoords */
        $flowerAtCoords = $itemsAtCoords[0];
        if ($flowerAtCoords->getUiData()['type'] !== $flower['color']) {
          throw new \BgaVisibleSystemException(
            "You cannot place a flower at $x, $y because their colors don't match"
          );
        }
      } else {
        if (in_array(['x' => $x, 'y' => $y], $playerBoard->getWaterSpaces())) {
          throw new \BgaVisibleSystemException("You cannot place a flower at $x, $y as it is a water space");
        }
      }
    }

    if ($playerBoard->getAmountOfMeeples() > 0) {
      $foundAdjacent = false;
      foreach ($flowers as $flower) {
        $x = $flower['x'];
        $y = $flower['y'];
        foreach ([[$x, $y], [$x - 1, $y], [$x + 1, $y], [$x, $y - 1], [$x, $y + 1]] as [$adjacentX, $adjacentY]) {
          $itemsAtCoords = $playerBoard->getItemsAt($adjacentX, $adjacentY);
          if (!is_null($itemsAtCoords) && count($itemsAtCoords) > 0) {
            $foundAdjacent = true;
            break;
          }
        }
      }
      if (!$foundAdjacent) {
        throw new \BgaVisibleSystemException(
          "New flowers must be adjacent to, or on top of, Flowers you already placed"
        );
      }
    }
  }

  /**
   * @throws \BgaVisibleSystemException
   */
  public function verifyAnimalParams(Player $player, int $requestedZone, array $finishedZonesIdsBeforePlacing): void
  {
    $playerBoard = $player->board();
    $finishedZones = $playerBoard->getFullyFilledZones();
    $zonesIdsFinishedThisTurn = array_filter(
      array_keys($finishedZones),
      function ($zoneId) use ($finishedZonesIdsBeforePlacing) {
        return !in_array($zoneId, $finishedZonesIdsBeforePlacing);
      }
    );

    if (!in_array($requestedZone, array_keys($playerBoard->getFullyFilledZones()))) {
      throw new \BgaVisibleSystemException(
        "Unable to place an animal: Requested animal zone is not fully filled yet"
      );
    }
    if (!in_array($requestedZone, $zonesIdsFinishedThisTurn)) {
      throw new \BgaVisibleSystemException(
        "Unable to place an animal: Requested animal zone was fully filled before this turn"
      );
    }

    $finishedZone = $finishedZones[$requestedZone];
    $flowerColors = Utils::getFlowerColorsForZone($playerBoard, $finishedZone);
    if (count($flowerColors) > 1) {
      throw new \BgaVisibleSystemException("Unable to place an animal: zone contains flowers of different colors");
    }
  }

  /**
   * @throws \BgaVisibleSystemException
   */
  public function verifyFertilizedParams(Player $player, Meeple $animal, array $params): void
  {
    $animalCoords = $animal->getCoords();
    foreach ($params as $placedItem) {
      $x = $placedItem['x'];
      $y = $placedItem['y'];
      $distance = abs($x - $animalCoords['x']) + abs($y - $animalCoords['y']);
      if ($distance === 0) {
        throw new \BgaVisibleSystemException(
          "Unable to fertilize: trying to place a flower/tree at the same tile as the animal"
        );
      }
      if ($distance > 1) {
        throw new \BgaVisibleSystemException(
          "Unable to fertilize: placed flowers/trees should be adjacent"
        );
      }
      $board = $player->board();
      $itemsAtCell = $board->getItemsAt($x, $y);
      if (count($itemsAtCell) > 1) {
        throw new \BgaVisibleSystemException(
          "Unable to fertilize: cell $x, $y already has 2 meeples on it"
        );
      } elseif (count($itemsAtCell) === 1) {
        if (isset($placedItem['color'])) { // Sometimes frontend places a second flower without a color during fertilization and that's fine
          $itemColor = $placedItem['color'];
          $itemPlacedColor = $itemsAtCell[0]->getType();
          if ($itemColor !== $itemPlacedColor) {
            throw new \BgaVisibleSystemException(
              "Unable to fertilize: trying to place a flower of incorrect color $itemColor. Found color $itemPlacedColor at the same cell"
            );
          }
        }
      }
      if (in_array(['x' => $x, 'y' => $y], $board->getWaterSpaces())) {
        throw new \BgaVisibleSystemException("Unable to fertilize: trying to place a flower on a water space");
      }
    }
  }
}
