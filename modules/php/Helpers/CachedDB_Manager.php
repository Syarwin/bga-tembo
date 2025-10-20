<?php

namespace Bga\Games\Tembo\Helpers;

class CachedDB_Manager extends DB_Manager
{
  protected static string $table = "";
  protected static string $primary = "";
  protected static bool $log = true;
  protected static ?Collection $datas = null;

  protected static function cast($row)
  {
    return $row;
  }

  public static function fetchIfNeeded()
  {
    if (is_null(static::$datas)) {
      static::$datas = static::DB()->get();
    }
  }

  public static function invalidate()
  {
    static::$datas = null;
  }

  public static function getAll(): ?Collection
  {
    self::invalidate();
    self::fetchIfNeeded();
    return static::$datas;
  }

  public static function get($id)
  {
    return self::getAll()
      ->filter(function ($obj) use ($id) {
        return $obj->getId() == $id;
      })
      ->first();
  }
}
