<?php

namespace Bga\Games\Tembo\Managers;

use Bga\Games\Tembo\Helpers\Collection;
use Bga\Games\Tembo\Helpers\CachedPieces;
use Bga\Games\Tembo\Models\Meeple;
use Bga\Games\Tembo\Models\Player;

require_once dirname(__FILE__) . "/../Materials/Journeys.php";

class Meeples extends CachedPieces
{
  protected static string $table = 'meeples';
  protected static string $prefix = 'meeple_';
  protected static array $customFields = ['type', 'player_id', 'x', 'y'];
  protected static ?Collection $datas = null;
  protected static bool $autoremovePrefix = false;

  protected static function cast($row): Meeple
  {
    return new Meeple($row);
  }

  public static function getUiData(): array
  {
    return self::getAll()->filter(fn($meeple) => $meeple->getLocation() != 'box')->toArray();
  }

  public static function getOfPlayer($pId): Collection
  {
    return self::getAll()->where('pId', $pId);
  }

  public static function getOnCell($hex): Collection
  {
    return self::getAll()
      ->where('location', 'board')
      ->where('x', $hex['x'])
      ->where('y', $hex['y']);
  }

  public static function layTree(int $spaceType): bool
  {
    $treeType = [
      SPACE_TREE_GREEN => TREE_GREEN,
      SPACE_TREE_RED => TREE_RED,
      SPACE_TREE_BROWN => TREE_BROWN,
      SPACE_TREE_TEAL => TREE_TEAL,
    ][$spaceType];
    return static::layMeeple($treeType);
  }

  public static function layLandmark(int $caredRefType): string
  {
    $landmarkType = [
      CARD_REF_SINGLE_SNOW => LANDMARK_SNOW,
      CARD_REF_DIAGONAL_MEADOW => LANDMARK_MEADOW,
      CARD_REF_L_SHAPED_RIVER => LANDMARK_RIVER,
      CARD_REF_V_ROCKS => LANDMARK_ROCKS,
      CARD_REF_DIAGONAL_CANYON => LANDMARK_CANYON,
      CARD_REF_CORNER_WATERFALL => LANDMARK_WATERFALL,
    ][$caredRefType];
    static::layMeeple($landmarkType);
    return $landmarkType;
  }

  private static function layMeeple(string $type): bool
  {
    $requiredMeeple = static::getAll()->filter(fn($meeple) => $meeple->getType() === $type)->first();
    if (is_null($requiredMeeple)) {
      throw new \BgaVisibleSystemException('No landmark/tree of type ' . $type . ' available');
    }
    if ($requiredMeeple->getState() === STATE_STANDING) {
      $requiredMeeple->setState(STATE_LAYING);
      return true;
    } else {
      return false;
    }
  }

  public static function eliminateElephant(int $pId, int $state)
  {
    /** @var Meeple $elephant */
    $elephant = self::getTiredRestedElephants($pId, $state)->rand();
    $elephant->setLocation(LOCATION_DISCARD);
  }

  // public static function getUnitsOnCell($hex)
  // {
  //   return self::getOnCell($hex)->where('type', [WORKER, MECH, CHARACTER]);
  // }

  // public static function getBuildingsOnCell($hex)
  // {
  //   return self::getOnCell($hex)->where('type', BUILDINGS);
  // }

  // public static function getResourcesOnCell($hex, $includeWorkers = false)
  // {
  //   return self::getOnCell($hex)->where('type', $includeWorkers ? PRODUCABLE_RESOURCES : RESOURCES);
  // }

  // public static function getResourcesOnCellByTypes($hex, $includeWorkers = false, $onlyIds = false)
  // {
  //   $resources = [];
  //   foreach (self::getResourcesOnCell($hex, $includeWorkers) as $mId => $meeple) {
  //     $resources[$meeple->getType()][] = $onlyIds ? $mId : $meeple;
  //   }
  //   return $resources;
  // }


  ////////////////////////////////////
  //  ____       _
  // / ___|  ___| |_ _   _ _ __
  // \___ \ / _ \ __| | | | '_ \
  //  ___) |  __/ |_| |_| | |_) |
  // |____/ \___|\__|\__,_| .__/
  //                      |_|
  ////////////////////////////////////

  /* Creation of various meeples */
  public static function setupNewGame(int $journeyId, array $board): Collection
  {
    $meeples = [];
    foreach ([TREE_GREEN, TREE_RED, TREE_BROWN, TREE_TEAL] as $type) {
      $meeples[] = ['type' => $type, 'state' => STATE_STANDING];
    }

    foreach (ALL_LANDMARKS as $landmark) {
      $meeples[] = ['type' => $landmark, 'state' => STATE_STANDING, 'location' => LOCATION_RESERVE];
    }

    $journey = JOURNEYS[$journeyId];
    if (isset($journey['lion'])) {
      $meeples[] = [
        'type' => LION,
        'state' => STATE_LAYING,
        'location' => LOCATION_BOARD,
        'x' => $journey['lion']['x'],
        'y' => $journey['lion']['y']
      ];
    }
    if (isset($journey['lioness'])) {
      $meeples[] = [
        'type' => LIONESS,
        'state' => STATE_LAYING,
        'location' => LOCATION_BOARD,
        'x' => $journey['lioness']['x'],
        'y' => $journey['lioness']['y']
      ];
    }

    $elephantsMap = [3, 3, 2, 2, 3];
    $allPlayers = Players::getAll();
    /** @var Player $player */
    foreach ($allPlayers as $player) {
      foreach ($elephantsMap as $elephantType => $elephantAmount) {
        for ($i = 0; $i < $elephantAmount; $i++) {
          $meeples[] = [
            'type' => ELEPHANT . '-' . $elephantType + 1,
            'state' => STATE_TIRED,
            'location' => LOCATION_RESERVE . '-' . $player->getId(),
            'player_id' => $player->getId(),
          ];
        }
      }
    }

    $meeples[] = [
      'type' => MATRIARCH,
      'location' => LOCATION_BOARD,
      'x' => $journey['start']['x'] + 1,
      'y' => $journey['start']['y'],
    ];

    $meeples = self::create($meeples, LOCATION_TABLE);
    foreach ($allPlayers as $player) {
      $player->gainElephants(3, '');
    }
    return $meeples;
  }

  /**
   * Finish the setup of a player once he is done with map selection by creating tokens on that map
   */
  public static function setupPlayer($player)
  {
    $pId = $player->getId();
    $meeples = [];

    return self::create($meeples);
  }

  public static function setupPlayers($players)
  {
    $meeples = new Collection();
    foreach ($players as $player) {
      $meeples = $meeples->merge(self::setupPlayer($player));
    }
    return $meeples;
  }

  public static function getLions(): array
  {
    return array_map(fn($lionType) => self::getAll()->filter(fn($meeple) => $meeple->getType() === $lionType)->first(),
      [LIONESS, LION]);
  }

  public static function getTrees(): Collection
  {
    return self::getAll()->filter(fn($meeple) => in_array($meeple->getType(), ALL_TREES));
  }

  public static function getLandmarks(): array
  {
    return self::getAll()->filter(fn($meeple) => in_array($meeple->getType(), ALL_LANDMARKS))->toArray();
  }

  public static function gainElephants(int $pId, int $amount): array
  {
    return static::gainLoseElephants($pId, $amount);
  }

  public static function loseElephants(int $pId, int $amount): array
  {
    return static::gainLoseElephants($pId, $amount, false);
  }

  private static function gainLoseElephants(int $pId, int $amount, bool $gain = true): array
  {
    $state = $gain ? STATE_TIRED : STATE_RESTED;
    $requiredElephants = static::getTiredRestedElephants($pId, $state);
    if ($amount > $requiredElephants->count()) {
      $amount = $requiredElephants->count();
    }
    $requiredElephants = $requiredElephants->toArray();
    shuffle($requiredElephants);
    $elephants = [];
    for ($i = 0; $i < $amount; $i++) {
      $elephant = array_pop($requiredElephants);
      $elephant->setState($state === STATE_RESTED ? STATE_TIRED : STATE_RESTED);
      $elephants[] = $elephant;
    }
    return $elephants;
  }

  public static function getTiredRestedElephants(int $pId, int $state): Collection
  {
    return static::getElephants($pId)->filter(fn($elephant) => $elephant->getState() === $state);
  }

  private static function getElephants(int $pId = null): Collection
  {
    $allElephants = self::getAll()->filter(
    /** @var Meeple $meeple */
      fn($meeple) => $meeple->isElephant()
    );
    return is_null($pId) ? $allElephants : $allElephants->filter(fn($elephant) => $elephant->getPId() === $pId);
  }

  public static function getElephantsOnBoard(): Collection
  {
    return self::getElephants()->filter(fn($elephant) => $elephant->getLocation() === LOCATION_BOARD);
  }

  public static function getMatriarch(): Meeple
  {
    return self::getAll()->where('type', MATRIARCH)->first();
  }

  public static function placeElephantsOnBoard(int $pId, array $coords, bool $isMatriarch = false): array
  {
    $elephants = [];
    if (!$isMatriarch) {
      $elephantsOfPlayer = static::getTiredRestedElephants($pId, STATE_RESTED)->toArray();
      $coordsCount = count($coords);
      $elephantsCount = count($elephantsOfPlayer);
      if ($elephantsCount < $coordsCount) {
        throw new \BgaVisibleSystemException("placeElephantOnBoard: player with id {$pId} does not have enough elephants ({$coordsCount}, needed $elephantsCount)");
      }
      shuffle($elephantsOfPlayer);
    }
    foreach ($coords as $coord) {
      for ($i = 0; $i < $coord['amount']; $i++) {
        $elephant = $isMatriarch ? static::getMatriarch() : array_shift($elephantsOfPlayer);
        $elephant->setX($coord['x']);
        $elephant->setY($coord['y']);
        $elephant->setLocation(LOCATION_BOARD);
        $elephants[] = $elephant;
      }
    }
    return $elephants;
  }

  public static function gatherHerd(): void
  {
    $elephants = self::getElephantsOnBoard();
    /** @var Meeple $elephant */
    foreach ($elephants as $elephant) {
      $elephant->setLocation(LOCATION_RESERVE . '-' . $elephant->getPId());
      $elephant->setState(STATE_TIRED);
    }
  }

  public static function refreshTrees()
  {
    $trees = self::getTrees();
    /** @var Meeple $tree */
    foreach ($trees as $tree) {
      $tree->setState(STATE_STANDING);
    }
  }

  // public static function createResourceOnHex($type, $x, $y)
  // {
  //   $meeple = [
  //     'type' => $type,
  //     'location' => 'board',
  //     'x' => $x,
  //     'y' => $y,
  //   ];

  //   return self::singleCreate($meeple);
  // }

  // public static function placeOrCreateResourceOnHex($player, $type, $x, $y)
  // {
  //   if ($type == WORKER) {
  //     $worker =  $player->getWorkersInReserve()->first();
  //     if (is_null($worker)) {
  //       throw new \BgaVisibleSystemException('Not enough workers to produce. Should not happen');
  //     }
  //     Stats::incWorkersDeployed($player);
  //     $worker->moveTo(['x' => $x, 'y' => $y]);
  //     return $worker;
  //   } else {
  //     Stats::incResourcesGained($player);
  //     return self::createResourceOnHex($type, $x, $y);
  //   }
  // }
}
