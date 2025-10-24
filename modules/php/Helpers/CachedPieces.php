<?php

namespace Bga\Games\Tembo\Helpers;

use Collator;

/*
 * This is a generic class to manage game pieces.
 *
 * On DB side this is based on a standard table with the following fields:
 * %prefix_%id (string), %prefix_%location (string), %prefix_%state (int)
 *
 *
 * CREATE TABLE IF NOT EXISTS `token` (
 * `token_id` varchar(32) NOT NULL,
 * `token_location` varchar(32) NOT NULL,
 * `token_state` int(10),
 * PRIMARY KEY (`token_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 * CREATE TABLE IF NOT EXISTS `card` (
 * `card_id` int(32) NOT NULL AUTO_INCREMENT,,
 * `card_location` varchar(32) NOT NULL,
 * `card_state` int(10),
 * PRIMARY KEY (`card_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 */

class CachedPieces extends DB_Manager
{
  protected static string $table = "";

  protected static string $prefix = 'piece_';
  protected static bool $autoIncrement = true;
  protected static string $primary;
  protected static bool $autoremovePrefix = true;
  protected static bool $autoreshuffle = false; // If true, a new deck is automatically formed with a reshuffled discard as soon at is needed
  protected static ?array $autoreshuffleListener = null; // Callback to a method called when an autoreshuffle occurs
  // autoreshuffleListener = array( 'obj' => object, 'method' => method_name )
  // If defined, tell the name of the deck and what is the corresponding discard (ex : "mydeck" => "mydiscard")
  protected static array $autoreshuffleCustom = [];
  protected static array $customFields = [];
  protected static ?Collection $datas = null;

  public static function DB($table = null): QueryBuilder
  {
    static::$primary = static::$prefix . 'id';
    return parent::DB(static::$table);
  }

  /****
   * Return the basic select query fetching basic fields and custom fields
   */
  final static function getSelectQuery(): QueryBuilder
  {
    $basic = [
      'id' => static::$prefix . 'id',
      'location' => static::$prefix . 'location',
      'state' => static::$prefix . 'state',
    ];
    if (!static::$autoremovePrefix) {
      $basic = array_values($basic);
    }

    $query = self::DB()->select(array_merge($basic, static::$customFields));
    return $query;
  }

  public static function fetchIfNeeded(): void
  {
    if (is_null(static::$datas)) {
      static::$datas = static::getSelectQuery()->get();
    }
  }

  public static function invalidate(): void
  {
    static::$datas = null;
  }

  /************************************
   *************************************
   ********* QUERY BUILDER *************
   *************************************
   ************************************/

  public static function where(string $field, mixed $value): Collection
  {
    return self::getAll()->where($field, $value);
  }

  /****
   * Append the basic select query with a where clause
   */
  public static function getSelectWhere(
    null|int|string $id = null,
    ?string $location = null,
    ?int $state = null
  ): Collection
  {
    return self::where('id', $id)
      ->where('location', $location)
      ->where('state', $state);
  }

  /**
   * Get all the pieces
   */
  public static function getAll(): Collection
  {
    static::fetchIfNeeded();
    return static::$datas;
  }

  /************************************
   *************************************
   ********* SANITY CHECKS *************
   *************************************
   ************************************/

  /*
   * Check that the location only contains alphanum and underscore character
   *  -> if the location is an array, implode it using underscores
   */
  final static function checkLocation(string &$location, bool $like = false): void
  {
    if (is_null($location)) {
      throw new \BgaVisibleSystemException('Class Pieces: location cannot be null');
    }

    if (is_array($location)) {
      $location = implode('-', $location);
    }

    $extra = $like ? '%' : '';
    if (preg_match("/^[A-Za-z0-9$extra-][A-Za-z_0-9$extra-]*$/", $location) == 0) {
      throw new \BgaVisibleSystemException(
        "Class Pieces: location must be alphanum and underscore non empty string '$location'"
      );
    }
  }

  /*
   * Check that the id is alphanum and underscore
   */
  final static function checkId(string|int &$id, bool $like = false): void
  {
    if (is_null($id)) {
      throw new \BgaVisibleSystemException('Class Pieces: id cannot be null');
    }

    $extra = $like ? '%' : '';
    if (preg_match("/^[A-Za-z_0-9$extra-]+$/", $id) == 0) {
      throw new \BgaVisibleSystemException("Class Pieces: id must be alphanum and underscore non empty string '$id'");
    }
  }

  final static function checkIdArray(array $arr): void
  {
    if (is_null($arr)) {
      throw new \BgaVisibleSystemException('Class Pieces: tokens cannot be null');
    }

    if (!is_array($arr)) {
      throw new \BgaVisibleSystemException('Class Pieces: tokens must be an array');
      foreach ($arr as $id) {
        self::checkId($id);
      }
    }
  }

  /*
   * Check that the state is an integer
   */
  final static function checkState(int $state, bool $canBeNull = false): void
  {
    if (is_null($state) && !$canBeNull) {
      throw new \BgaVisibleSystemException('Class Pieces: state cannot be null');
    }

    if (!is_null($state) && preg_match('/^-*[0-9]+$/', $state) == 0) {
      throw new \BgaVisibleSystemException('Class Pieces: state must be integer number');
    }
  }

  /*
   * Check that a given variable is a positive integer
   */
  final static function checkPosInt(int $n): void
  {
    if ($n && preg_match('/^[0-9]+$/', $n) == 0) {
      throw new \BgaVisibleSystemException('Class Pieces: number of pieces must be integer number');
    }
  }

  /************************************
   *************************************
   ************** GETTERS **************
   *************************************
   ************************************/

  /**
   * Get specific piece by id
   */
  public static function get(int $id, bool $raiseExceptionIfNotEnough = true)
  {
    $result = self::getMany($id, $raiseExceptionIfNotEnough);
    return $result->count() == 1 ? $result->first() : $result;
  }

  public static function getMany(int|string|array $ids, bool $raiseExceptionIfNotEnough = true): Collection
  {
    if (!is_array($ids)) {
      $ids = [$ids];
    }

    self::checkIdArray($ids);
    static::fetchIfNeeded();
    $result = new Collection([]);
    foreach ($ids as $id) {
      $piece = static::$datas[$id] ?? null;
      if ($piece) {
        $result[$id] = $piece;
      }
    }

    if (count($result) != count($ids) && $raiseExceptionIfNotEnough) {
      $found = count($result);
      $searched = count($ids);
      throw new \feException(print_r(\debug_print_backtrace()));
      throw new \feException(
        "Class Pieces: getMany, some pieces have not been found ! ($found on $searched)(" . json_encode(
          $ids
        ) . " et " . json_encode($result->getIds())
      );
    }

    return $result;
  }

  public static function getSingle(int|string $id, bool $raiseExceptionIfNotEnough = true): mixed
  {
    $result = self::getMany([$id], $raiseExceptionIfNotEnough);
    return $result->count() == 1 ? $result->first() : null;
  }

  /**
   * Get max or min state of the specific location
   */
  public static function getExtremePosition(bool $getMax, string $location): int
  {
    $states = self::getInLocation($location)
      ->map(function ($obj) {
        return $obj->getState();
      })
      ->toArray();
    return empty($states) ? 0 : ($getMax ? max($states) : min($states));
  }

  /**
   * Return "$nbr" piece on top of this location, top defined as item with higher state value
   */
  public static function getTopOf(string $location, int $n = 1): Collection
  {
    self::checkLocation($location);
    self::checkPosInt($n);
    return self::getInLocation($location)
      ->orderBy('state', 'DESC')
      ->limit($n);
  }

  /**
   * Return all pieces in specific location
   * note: if "order by" is used, result object is NOT indexed by ids
   */
  public static function getInLocation(string $location, ?int $state = null): Collection
  {
    self::checkLocation($location, true);
    self::checkState($state, true);
    return self::getSelectWhere(null, $location, $state);
  }

  public static function getInLocationOrdered(string $location, ?int $state = null): Collection
  {
    return self::getInLocation($location, $state)->orderBy('state', 'ASC');
  }

  /**
   * Return number of pieces in specific location
   */
  public static function countInLocation(string $location, ?int $state = null): int
  {
    self::checkLocation($location, true);
    self::checkState($state, true);
    return self::getInLocation($location, $state)->count();
  }

  /**
   * getFilteredQuery : many times the DB scheme has a pId and a type extra field, this allow for a shortcut for a query for these case
   */
  public static function getFiltered(int $pId, ?string $location = null, ?string $type = null): Collection
  {
    return self::getSelectWhere(null, $location, null)
      ->where('pId', $pId)
      ->where('type', $type);
  }

  /************************************
   *************************************
   ************** SETTERS **************
   *************************************
   ************************************/
  public static function setState(int|string $id, int $state): mixed
  {
    self::checkState($state);
    self::checkId($id);
    return self::getSingle($id)->setState($state);
  }

  /*
   * Move one (or many) pieces to given location
   */
  public static function move(int|string|array $ids, string $location, int $state = 0): Collection
  {
    if (!is_array($ids)) {
      $ids = [$ids];
    }
    if (empty($ids)) {
      return new Collection([]);
    }

    self::checkLocation($location);
    self::checkState($state);
    self::checkIdArray($ids);
    return self::getMany($ids)
      ->update('location', $location)
      ->update('state', $state);
  }

  /*
   *  Move all tokens from a location to another
   *  !!! state is reset to 0 or specified value !!!
   *  if "fromLocation" and "fromState" are null: move ALL cards to specific location
   */
  public static function moveAllInLocation(
    string $fromLocation,
    string $toLocation,
    ?int $fromState = null,
    ?int $toState = 0
  ): Collection
  {
    if (!is_null($fromLocation)) {
      self::checkLocation($fromLocation);
    }
    self::checkLocation($toLocation);

    return self::getInLocation($fromLocation, $fromState)
      ->update('location', $toLocation)
      ->update('state', $toState);
  }

  /**
   * Move all pieces from a location to another location arg stays with the same value
   */
  public static function moveAllInLocationKeepState(string $fromLocation, string $toLocation): Collection
  {
    self::checkLocation($fromLocation);
    self::checkLocation($toLocation);
    return self::moveAllInLocation($fromLocation, $toLocation, null, null);
  }

  /*
   * Pick the first "$nbr" pieces on top of specified deck and place it in target location
   * Return pieces infos or void array if no card in the specified location
   */
  public static function pickForLocation(
    int $nbr,
    string $fromLocation,
    string $toLocation,
    int $state = 0,
    bool $deckReform = true
  ): Collection
  {
    self::checkLocation($fromLocation);
    self::checkLocation($toLocation);
    $pieces = self::getTopOf($fromLocation, $nbr);
    $ids = $pieces->getIds();
    $pieces = self::getMany($ids)
      ->update('location', $toLocation)
      ->update('state', $state);

    // No more pieces in deck & reshuffle is active => form another deck
    if (
      array_key_exists($fromLocation, static::$autoreshuffleCustom) &&
      count($pieces) < $nbr &&
      static::$autoreshuffle &&
      $deckReform
    ) {
      $missing = $nbr - count($pieces);
      self::reformDeckFromDiscard($fromLocation);
      $pieces = $pieces->merge(
        self::pickForLocation($missing, $fromLocation, $toLocation, $state, false)
      ); // Note: block another deck reform
    }

    return $pieces;
  }

  public static function pickOneForLocation(
    string $fromLocation,
    string $toLocation,
    int $state = 0,
    bool $deckReform = true
  ): mixed
  {
    return self::pickForLocation(1, $fromLocation, $toLocation, $state, $deckReform)->first();
  }

  /*
   * Reform a location from another location when enmpty
   */
  public static function reformDeckFromDiscard(string $fromLocation): void
  {
    self::checkLocation($fromLocation);
    if (!array_key_exists($fromLocation, static::$autoreshuffleCustom)) {
      throw new \BgaVisibleSystemException(
        "Class Pieces:reformDeckFromDiscard: Unknown discard location for $fromLocation !"
      );
    }

    $discard = static::$autoreshuffleCustom[$fromLocation];
    self::checkLocation($discard);
    self::moveAllInLocation($discard, $fromLocation);
    self::shuffle($fromLocation);
    if (static::$autoreshuffleListener) {
      $obj = static::$autoreshuffleListener['obj'];
      $method = static::$autoreshuffleListener['method'];
      call_user_func([$obj, $method], $fromLocation);
    }
  }

  /*
   * Shuffle pieces of a specified location, result of the operation will changes state of the piece to be a position after shuffling
   */
  public static function shuffle(string $location): void
  {
    self::checkLocation($location);
    $pieces = self::getInLocation($location)->getIds();
    shuffle($pieces);
    foreach ($pieces as $state => $id) {
      self::getSingle($id)->setState($state);
    }
  }

  public static function insertOnTop(int|string $id, string $location): void
  {
    $pos = self::getExtremePosition(true, $location);
    self::move($id, $location, $pos + 1);
  }

  public static function insertAtBottom(int|string $id, string $location): void
  {
    $pos = self::getExtremePosition(false, $location);
    self::move($id, $location, $pos - 1);
  }

  /************************************
   ******** CREATE NEW PIECES **********
   ************************************/

  /* This inserts new records in the database.
   * Generically speaking you should only be calling during setup
   *  with some rare exceptions.
   *
   * Pieces is an array with at least the following fields:
   * [
   *   [
   *     "id" => <unique id>    // This unique alphanum and underscore id, use {INDEX} to replace with index if 'nbr' > 1, i..e "meeple_{INDEX}_red"
   *     "nbr" => <nbr>           // Number of tokens with this id, optional default is 1. If nbr >1 and id does not have {INDEX} it will throw an exception
   *     "nbrStart" => <nbr>           // Optional, if the indexing does not start at 0
   *     "location" => <location>       // Optional argument specifies the location, alphanum and underscore
   *     "state" => <state>             // Optional argument specifies integer state, if not specified and $token_state_global is not specified auto-increment is used
   */

  public static function create(
    array $pieces,
    ?string $globalLocation = null,
    ?int $globalState = null,
    ?int $globalId = null
  ): Collection
  {
    $pos = is_null($globalLocation) ? 0 : self::getExtremePosition(true, $globalLocation) + 1;

    $values = [];
    $ids = [];
    foreach ($pieces as $info) {
      $n = $info['nbr'] ?? 1;
      $start = $info['nbrStart'] ?? 0;
      $id = $info['id'] ?? $globalId;
      $location = $info['location'] ?? $globalLocation;
      $state = $info['state'] ?? $globalState;
      if (is_null($state)) {
        $state = $location == $globalLocation ? $pos++ : 0;
      }

      // SANITY
      if (is_null($id) && !static::$autoIncrement) {
        throw new \BgaVisibleSystemException('Class Pieces: create: id cannot be null if not autoincrement');
      }

      if (is_null($location)) {
        throw new \BgaVisibleSystemException(
          'Class Pieces : create location cannot be null (set per token location or location_global'
        );
      }
      self::checkLocation($location);

      for ($i = $start; $i < $n + $start; $i++) {
        $data = [];
        if (static::$autoIncrement) {
          $data = [$location, $state];
        } else {
          $nId = preg_replace('/\{INDEX\}/', $id == $globalId ? count($ids) : $i, $id);
          self::checkId($nId);
          $data = [$nId, $location, $state];
          $ids[] = $nId;
        }

        foreach (static::$customFields as $field) {
          $data[] = $info[$field] ?? null;
        }

        $values[] = $data;
      }
    }

    $p = static::$prefix;
    $fields = static::$autoIncrement ? [$p . 'location', $p . 'state'] : [$p . 'id', $p . 'location', $p . 'state'];
    foreach (static::$customFields as $field) {
      $fields[] = $field;
    }

    // With auto increment, we compute the set of all consecutive ids
    self::fetchIfNeeded();

    $ids = self::DB()
      ->multipleInsert($fields)
      ->values($values);

    foreach (
      static::getSelectQuery()
        ->whereIn(static::$prefix . 'id', $ids)
        ->get()
      as $id => $obj
    ) {
      static::$datas[$id] = $obj;
    }

    return self::getMany($ids);
  }

  /*
   * Create a single token
   */
  public static function singleCreate(array $token): mixed
  {
    return self::create([$token])->first();
  }


  /**
   * Delete meeples
   */
  public static function delete(int|string|array $ids): Collection
  {
    if (!is_array($ids)) {
      $ids = [$ids];
    }
    if (empty($ids)) {
      return new Collection([]);
    }

    self::checkIdArray($ids);
    $pieces = self::getMany($ids);
    self::DB()
      ->delete()
      ->whereIn(static::$prefix . 'id', $ids)
      ->run();
    foreach ($ids as $id) {
      unset(static::$datas[$id]);
    }

    return $pieces;
  }
}
