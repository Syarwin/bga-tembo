<?php

namespace Bga\Games\Tembo\Managers;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Helpers\CachedDB_Manager;
use Bga\Games\Tembo\Helpers\Collection;
use Bga\Games\Tembo\Models\Player;
use Bga\Games\Tembo\Game;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */

class Players extends CachedDB_Manager
{
  protected static string $table = 'player';
  protected static string $primary = 'player_id';

  protected static function cast(array $row): Player
  {
    return new Player($row);
  }

  public static function setupNewGame(array $players, array $options): void
  {
    // Create players
    $gameInfos = Game::get()->getGameinfos();
    $colors = $gameInfos['player_colors'];
    $query = self::DB()->multipleInsert([
      'player_id',
      'player_color',
      'player_canal',
      'player_name',
      'player_avatar',
    ]);
    $values = [];
    $playerIndex = 0;
    foreach ($players as $pId => $player) {
      $color = $colors[$playerIndex++];
      $values[] = [
        $pId,
        $color,
        $player['player_canal'],
        $player['player_name'],
        $player['player_avatar'],
      ];
    }
    $query->values($values);
    self::invalidate();
    Game::get()->reattributeColorsBasedOnPreferences($players, $colors);
    Game::get()->reloadPlayersBasicInfos();
  }

  public static function getAll(int $except = null): Collection
  {
    $players = parent::getAll();
    if ($except) {
      $players = $players->filter(fn($player) => $player->getId() !== $except);
    }
    return $players;
  }

  public static function getActiveId(): int
  {
    return (int) Game::get()->getActivePlayerId();
  }

  public static function getCurrentId(bool $bReturnNullIfNotLogged = false): int
  {
    return (int) Game::get()->getCurrentPId($bReturnNullIfNotLogged);
  }

  public static function getActive(): Player
  {
    return self::get(self::getActiveId());
  }

  public static function getCurrent(): Player
  {
    return self::get(self::getCurrentId());
  }

  public static function get($id = null): Player
  {
    return parent::get($id ?? self::getActiveId());
  }

  public static function getNextId(Player|int $player): int
  {
    $pId = is_int($player) ?
      $player :
      $player->getId();
    $table = Game::get()->getNextPlayerTable();
    return $table[$pId];
  }

  public static function getNext(Player|int $player): Player
  {
    return self::get(self::getNextId($player));
  }

  /*
   * Return the number of players
   */
  public static function count(): int
  {
    return self::getAll()->count();
  }

  /*
   * getUiData : get all ui data of all players
   */
  public static function getUiData(?int $pId = null): array
  {
    return self::getAll()
      ->map(fn($player) => $player->getUiData($pId))
      ->toAssoc();
  }

  /*
   * Get current turn order according to first player variable
   */
  public static function getTurnOrder(?int $firstPlayer = null): array
  {
    $firstPlayer = $firstPlayer ?? Globals::getFirstPlayer();
    $order = [];
    $p = $firstPlayer;
    do {
      $order[] = $p;
      $p = self::getNextId($p);
    } while ($p != $firstPlayer);
    return $order;
  }
}
