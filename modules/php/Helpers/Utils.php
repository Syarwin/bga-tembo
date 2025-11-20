<?php

namespace Bga\Games\Tembo\Helpers;

abstract class Utils
{
  // COMPATIBLE WITH TURKISH I
  public static function ucfirst($str)
  {
    $tmp = preg_split('//u', $str, 2, PREG_SPLIT_NO_EMPTY);
    return mb_convert_case(str_replace('i', 'I', $tmp[0]), MB_CASE_TITLE, 'UTF-8') . ($tmp[1] ?? '');
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

  public static function search($array, $test)
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

  public static function someCellsIntersect(array $cells1, array $cells2): bool
  {
    return count(self::intersectZones($cells1, $cells2)) > 0;
  }

  public static function convertToSquareCoords($cell, $divideBy3 = true)
  {
    $divider = $divideBy3 ? 3 : 1;
    $cell['x'] = ($cell['x'] - ($cell['x'] % 3)) / $divider;
    $cell['y'] = ($cell['y'] - ($cell['y'] % 3)) / $divider;
    return $cell;
  }
}
