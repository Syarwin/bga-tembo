<?php

namespace Bga\Games\Tembo\Managers;

use Bga\Games\Tembo\Helpers\CachedPieces;
use Bga\Games\Tembo\Helpers\Collection;
use Bga\Games\Tembo\Models\Card;

require_once dirname(__FILE__) . "/../Materials/Cards.php";

class Cards extends CachedPieces
{
  protected static string $table = 'cards';
  protected static string $prefix = 'card_';
  protected static array $customFields = ['rotation', 'internal_id'];
  protected static null|Collection $datas = null;
  protected static bool $autoremovePrefix = false;
  protected static bool $autoIncrement = true;

  private static array $allCards = CARDS;

  protected static function cast(array $row): Card
  {
    return new Card($row);
  }

  public static function getUiData(): array
  {
    return self::getInLocation(LOCATION_TABLE)->ui();
  }

  public static function setupNewGame()
  {
    $startingCards = array_filter(static::getAllWithIds(), fn($card) => $card['deck'] === CARD_DECK_STARTING);
    shuffle($startingCards);

    foreach (Players::getAll() as $player) {
      $values = [];
      for ($k = 0; $k < 3; $k++) {
        $card = array_pop($startingCards);
        $values[] = [
          'internal_id' => $card['id'],
          'state' => 0,
        ];
      }
      static::create($values, LOCATION_HAND . '_' . $player->getId());
    }
  }

  public static function get(int $id, bool $raiseExceptionIfNotEnough = true): Card
  {
    return parent::get($id, $raiseExceptionIfNotEnough);
  }

  private static function getAllWithIds(): array
  {
    $result = [];
    foreach (static::$allCards as $id => $card) {
      $result[] = [...$card, 'id' => $id];
    }
    return $result;
  }
}
