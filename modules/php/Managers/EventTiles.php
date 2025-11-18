<?php

namespace Bga\Games\Tembo\Managers;

use Bga\Games\Tembo\Helpers\CachedPieces;
use Bga\Games\Tembo\Helpers\Collection;
use Bga\Games\Tembo\Models\EventTile;

require_once dirname(__FILE__) . "/../Materials/Events.php";

class EventTiles extends CachedPieces
{
  protected static string $table = 'event_tiles';
  protected static string $prefix = 'card_';
  protected static array $customFields = [];
  protected static null|Collection $datas = null;
  protected static bool $autoremovePrefix = false;
  protected static bool $autoIncrement = false;

  private static array $allCards = EVENTS;

  protected static function cast(array $row): EventTile
  {
    return new EventTile($row, EVENTS[$row['card_id']]);
  }

  public static function getUiData(): array
  {
    $current = self::getInLocation(LOCATION_BOARD);
    return $current->empty() ? [] : [
      'deckCount' => self::getInLocation(LOCATION_DECK)->count(),
      'active' => self::getInLocation(LOCATION_BOARD)->ui(),
    ];
  }

  public static function setupNewGame(): void
  {
    $values = [];
    $events = static::getAllFromMaterialsWithIds();
    shuffle($events);
    foreach ($events as $event) {
      $values[] = [
        'id' => $event['id'],
      ];
    }
    static::create($values, LOCATION_DECK);
    static::revealNext(false);
    static::revealNext(false);
  }

  private static function getAllFromMaterialsWithIds(): array
  {
    $result = [];
    foreach (static::$allCards as $id => $card) {
      $result[] = [...$card, 'id' => $id];
    }
    return $result;
  }

  public static function revealNext(bool $discardPrevious = true): array
  {
    /** @var EventTile $next */
    $next = static::getTopOf(LOCATION_DECK)->first();
    if (!is_null($next)) {
      if ($discardPrevious) {
        $rightEvent = static::getTopOf(LOCATION_BOARD)->first();
        static::move($rightEvent->getId(), LOCATION_DISCARD);
      }
      $leftEvent = static::getTopOf(LOCATION_BOARD)->first();
      if ($leftEvent->getType() === EVENT_TYPE_IMMEDIATE) {
        static::applyImmediateEffect($leftEvent);
      }
      static::move($next->getId(), LOCATION_BOARD, $next->getState());
      if ($next->getType() === EVENT_TYPE_IMMEDIATE) {
        static::applyImmediateEffect($next);
      }
    }
    return static::getUiData();
  }

  private static function applyImmediateEffect(EventTile $event): void
  {
    switch ($event->getEffect()) {

    }
  }
}
