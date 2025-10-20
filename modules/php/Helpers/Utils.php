<?php

namespace Bga\Games\Tembo\Helpers;

use Bga\Games\Tembo\Managers\ZooCards;
use Bga\Games\Tembo\Managers\ActionCards;
use Bga\Games\Tembo\Models\Board;

abstract class Utils extends \APP_DbObject
{
  // COMPATIBLE WITH TURKISH I
  public static function ucfirst($str)
  {
    $tmp = preg_split('//u', $str, 2, PREG_SPLIT_NO_EMPTY);
    return mb_convert_case(str_replace('i', 'I', $tmp[0]), MB_CASE_TITLE, 'UTF-8') . ($tmp[1] ?? '');
  }

  public static function getRankAndAmountOfKey($array, $targetKey): array
  {
    if (!array_key_exists($targetKey, $array)) {
      return [0, 0];
    }

    arsort($array); // Sorts by value in descending order while keeping keys

    $groupedPlayers = [];
    $previousValue = null;
    foreach ($array as $key => $value) {
      if ($value !== $previousValue) {
        $groupedPlayers[] = []; // Start a new group for a new value
      }
      $groupedPlayers[array_key_last($groupedPlayers)][] = $key; // Add key to the latest group
      $previousValue = $value;
    }
    foreach ($groupedPlayers as $key => $players) {
      if (in_array($targetKey, $players)) {
        return [$key + 1, $array[$targetKey]];
      }
    }

    return [0, 0];
  }

  public static function filter(&$data, $filter)
  {
    $data = array_values(array_filter($data, $filter));
  }

  public static function rand($array, $n = 1)
  {
    $keys = array_rand($array, $n);
    if ($n == 1) {
      $keys = [$keys];
    }
    $entries = [];
    foreach ($keys as $key) {
      $entries[] = $array[$key];
    }
    shuffle($entries);
    return $entries;
  }

  static function search($array, $test)
  {
    $found = false;
    $iterator = new \ArrayIterator($array);

    while ($found === false && $iterator->valid()) {
      if ($test($iterator->current())) {
        $found = $iterator->key();
      }
      $iterator->next();
    }

    return $found;
  }

  public static function die($args = null)
  {
    throw new \BgaVisibleSystemException(json_encode($args));
  }

  public static function tagTree($t, $tags)
  {
    foreach ($tags as $tag => $v) {
      $t[$tag] = $v;
    }

    if (isset($t['childs'])) {
      $t['childs'] = array_map(function ($child) use ($tags) {
        return self::tagTree($child, $tags);
      }, $t['childs']);
    }
    return $t;
  }

  public static function formatFee($cost)
  {
    return [
      'fees' => [$cost],
    ];
  }

  public static function uniqueZones($arr1)
  {
    if (empty($arr1)) {
      return [];
    }
    return array_values(
      array_uunique($arr1, function ($a, $b) {
        return $a['x'] == $b['x'] ?
          $a['y'] - $b['y'] :
          $a['x'] - $b['x'];
      })
    );
  }

  /**
   * Intersect two arrays of obj with keys x,y
   */
  public static function intersectZones($arr1, $arr2)
  {
    return array_values(
      \array_uintersect($arr1, $arr2, function ($a, $b) {
        return $a['x'] == $b['x'] ?
          $a['y'] - $b['y'] :
          $a['x'] - $b['x'];
      })
    );
  }

  /**
   * Diff two arrays of obj with keys x,y
   */
  public static function diffZones($arr1, $arr2)
  {
    return array_values(
      array_udiff($arr1, $arr2, function ($a, $b) {
        return $a['x'] == $b['x'] ?
          $a['y'] - $b['y'] :
          $a['x'] - $b['x'];
      })
    );
  }

  public static function bonus_diff($array1, $array2)
  {
    $result = [];
    foreach ($array1 as $key => $val) {
      if (!in_array($val, $array2)) {
        $result[] = $val;
      }
    }

    return $result;
  }

  const COLORS_CLASSES = [
    FLOWER_BLUE => 'icon-flower-blue',
    FLOWER_YELLOW => 'icon-flower-yellow',
    FLOWER_RED => 'icon-flower-red',
    FLOWER_WHITE => 'icon-flower-white',
    FLOWER_GREY => 'icon-flower-grey',
    FLOWER_JOKER => 'icon-flower-joker',
    TREE => 'icon-tree',
  ];

  public static function allColorsToClasses(array $colors): array
  {
    return array_map(function ($color) {
      return Utils::colorToClass($color);
    }, $colors);
  }

  public static function colorToClass(string $color): string
  {
    return self::COLORS_CLASSES[$color];
  }

  public static function classToColor(string $class): string
  {
    return array_search($class, self::COLORS_CLASSES);
  }

  public static function getFlowerColorsForZone(Board $board, array $zone): array
  {
    $colors = array_map(function ($cell) use ($board, $zone) {
      $x = $cell['x'];
      $y = $cell['y'];
      $meeplesAtCell = $board->getItemsAt($x, $y);
      if (count($meeplesAtCell) === 0) {
        return null;
      } else {
        $flower = array_values(array_filter($meeplesAtCell, function ($meeple) {
          return $meeple->getType() !== TREE && !in_array($meeple->getType(), ANIMALS);
        }))[0] ?? null;
        return $flower->getType();
      }
    }, $zone['cells']);
    return array_unique(array_filter($colors));
  }
}

function array_uunique($array, $comparator)
{
  $unique_array = [];
  do {
    $element = array_shift($array);
    $unique_array[] = $element;

    $array = array_udiff($array, [$element], $comparator);
  } while (count($array) > 0);

  return $unique_array;
}
