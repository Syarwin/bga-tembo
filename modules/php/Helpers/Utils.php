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

  public static function someCellsIntersect(array $cells1, array $cells2): bool
  {
    foreach ($cells1 as $cell) {
      if (in_array($cell, $cells2)) {
        return true;
      }
    }
    return false;
  }
}
