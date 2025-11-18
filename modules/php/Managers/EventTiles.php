<?php

namespace Bga\Games\Tembo\Managers;

use Bga\Games\Tembo\Actions\ActivateLions;
use Bga\Games\Tembo\Core\Notifications;
use Bga\Games\Tembo\Helpers\CachedPieces;
use Bga\Games\Tembo\Helpers\Collection;
use Bga\Games\Tembo\Models\EventTile;
use Bga\Games\Tembo\Models\Meeple;
use Bga\Games\Tembo\Models\Player;

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
      if ($leftEvent && $leftEvent->getType() === EVENT_TYPE_IMMEDIATE) {
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
      case EVENT_EFFECT_TREE_LAY:
        $treeType = $event->getArg();
        $msg = [
          TREE_GREEN => clienttranslate('A green tree standee is laid on its side'),
          TREE_RED => clienttranslate('A red tree standee is laid on its side'),
          TREE_BROWN => clienttranslate('A brown tree standee is laid on its side'),
          TREE_TEAL => clienttranslate('A blue tree standee is laid on its side'),
        ][$treeType];
        $success = Meeples::layMeeple($treeType);
        if ($success) {
          Notifications::immediateEvent($msg);
          Notifications::treesEaten(0, Meeples::getSingleOfType($treeType));
        } else {
          Notifications::immediateEventNoEffect($msg);
        }
        break;
      case EVENT_EFFECT_LOSE_2_ELEPHANTS:
        $msg = clienttranslate('All players should lose -2 rested Elephants');
        Notifications::immediateEvent($msg);
        /** @var Player $player */
        foreach (Players::getAll() as $player) {
          $player->gainElephants(-2);
        }
        break;
      case EVENT_EFFECT_LOSE_SUPPORT:
        $success = SupportTokens::lose();
        $msg = clienttranslate('-1 Support token');
        $success ? Notifications::immediateEvent($msg) : Notifications::immediateEventNoEffect($msg);
        break;
      case EVENT_EFFECT_LION_ACTIVATES:
        $lionType = $event->getArg();
        $msg = $lionType === LIONESS ? clienttranslate('Lioness activates') : clienttranslate('Lion activates');
        Notifications::immediateEvent($msg);
        ActivateLions::activate([$lionType]);
        break;
      case EVENT_EFFECT_ELEPHANT_DIES:
        $msg = clienttranslate('All players should remove 1 rested Elephant from the game (1 tired if no rested left)');
        Notifications::immediateEvent($msg);
        /** @var Player $player */
        foreach (Players::getAll() as $player) {
          $player->eliminateRestedOrTiredElephant();
        }
        break;
      case EVENT_EFFECT_LIONS_LAY:
        $msg = clienttranslate('Both Lions lay down on their side');
        Notifications::immediateEvent($msg);
        /** @var Meeple $lion */
        foreach (Meeples::getLions() as $lion) {
          $lion->setState(STATE_LAYING);
        }
        Notifications::lionMoved($lion);
        break;
      case EVENT_EFFECT_ENERGY:
        $msg = clienttranslate('Gain +1 Energy');
        $success = Energy::increase();
        if ($success) {
          Notifications::immediateEvent($msg);
          Notifications::energyIncreased(Energy::get(), 1);
        } else {
          Notifications::immediateEventNoEffect($msg);
        }
        break;
      default:
        throw new \BgaVisibleSystemException("Unknown event effect: " . $event->getEffect());
    }
  }
}
