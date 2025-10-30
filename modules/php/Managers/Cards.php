<?php

namespace Bga\Games\Tembo\Managers;

use Bga\Games\Tembo\Helpers\CachedPieces;
use Bga\Games\Tembo\Helpers\Collection;
use Bga\Games\Tembo\Models\Card;
use const Bga\Games\Tembo\OPTION_DIFFICULTY;

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

  public static function setupNewGame(array $options): void
  {
    $values = [];
    foreach ([CARD_DECK_THIRD, CARD_DECK_SECOND, CARD_DECK_FIRST] as $deck) {
      $cards = static::getFromDeck($deck);
      shuffle($cards);
      foreach ($cards as $card) {
        $values[] = [
          'internal_id' => $card['id'],
        ];
      }
    }
    $cards = static::getFromDeck(CARD_DECK_SUPPORT);
    shuffle($cards);
    $difficulty = (int) $options[OPTION_DIFFICULTY];
    $supportCardsAmount = [0 => 5, 1 => 4, 2 => 3, 3 => 2, 4 => 1, 5 => 0][$difficulty];
    for ($i = 0; $i < $supportCardsAmount; $i++) {
      $card = array_shift($cards);
      $values[] = [
        'internal_id' => $card['id'],
      ];
    }
    static::create($values, LOCATION_DECK);
  }

  public static function setupPlayersDecks(): void
  {
    $startingCards = static::getFromDeck(CARD_DECK_STARTING);
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

  private static function getFromDeck(int $deck): array
  {
    return array_filter(static::getAllFromMaterialsWithIds(), fn($card) => $card['deck'] === $deck);
  }

  public static function getRemaining(): array
  {
    $remaining = [
      CARD_DECK_FIRST => 0,
      CARD_DECK_SECOND => 0,
      CARD_DECK_THIRD => 0,
      CARD_DECK_SUPPORT => 0,
    ];
    $all = self::getAll();
    $allFromMaterials = self::getAllFromMaterialsWithIds();
    /** @var Card $card */
    foreach ($all as $card) {
      $cardDeck = $allFromMaterials[$card->getInternalId()]['deck'];
      if ($cardDeck !== CARD_DECK_STARTING) {
        $remaining[$cardDeck] += 1;
      }
    }
    return $remaining;
  }

  public static function get(int $id, bool $raiseExceptionIfNotEnough = true): Card
  {
    return parent::get($id, $raiseExceptionIfNotEnough);
  }

  private static function getAllFromMaterialsWithIds(): array
  {
    $result = [];
    foreach (static::$allCards as $id => $card) {
      $result[] = [...$card, 'id' => $id];
    }
    return $result;
  }
}
