<?php

namespace Bga\Games\Tembo\Helpers;

class CachedDB_Manager extends DB_Manager
{
  protected static string $table = "";
  protected static string $primary = "";
  protected static bool $log = true;
  protected static ?Collection $datas = null;

  protected static function cast(array $row): mixed
  {
    return $row;
  }

  public static function fetchIfNeeded(): void
  {
    if (is_null(static::$datas)) {
      static::$datas = static::DB()->get();
    }
  }

  public static function invalidate(): void
  {
    static::$datas = null;
  }

  public static function getAll(): ?Collection
  {
    self::invalidate();
    self::fetchIfNeeded();
    return static::$datas;
  }

  public static function get(int|string $id): mixed
  {
    return self::getAll()
      ->filter(function ($obj) use ($id) {
        return $obj->getId() == $id;
      })
      ->first();
  }

  public static function getMany(array $ids): Collection
  {
    return self::getAll()
      ->filter(function ($obj) use ($ids) {
        return in_array($obj->getId(), $ids);
      });
  }
}
