<?php

namespace Bga\Games\Tembo\Managers;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Helpers\CachedPieces;
use Bga\Games\Tembo\Helpers\Collection;
use Bga\Games\Tembo\Helpers\Utils;
use Bga\Games\Tembo\Models\Card;
use const Bga\Games\Tembo\OPTION_DIFFICULTY;

require_once dirname(__FILE__) . "/../Materials/Cards.php";

class Cards extends CachedPieces
{
  protected static string $table = 'cards';
  protected static string $prefix = 'card_';
  protected static array $customFields = ['x', 'y', 'rotation'];
  protected static null|Collection $datas = null;
  protected static bool $autoremovePrefix = false;
  protected static bool $autoIncrement = false;

  private static array $allCards = CARDS;

  protected static function cast(array $row): Card
  {
    return new Card($row);
  }

  public static function getUiData(): array
  {
    return self::getInLocation(LOCATION_BOARD)->ui();
  }

  public static function setupNewGame(array $options): void
  {
    $values = [];
    foreach ([CARD_DECK_THIRD, CARD_DECK_SECOND, CARD_DECK_FIRST] as $deck) {
      $cards = static::getFromDeck($deck);
      shuffle($cards);
      foreach ($cards as $card) {
        $values[] = [
          'id' => $card['id'],
        ];
      }
    }
    $cards = static::getFromDeck(CARD_DECK_SUPPORT);
    shuffle($cards);
    $difficulty = isset($options[OPTION_DIFFICULTY]) ? (int) $options[OPTION_DIFFICULTY] : 0;
    $supportCardsAmount = [0 => 5, 1 => 4, 2 => 3, 3 => 2, 4 => 1, 5 => 0][$difficulty];
    for ($i = 0; $i < $supportCardsAmount; $i++) {
      $card = array_shift($cards);
      $values[] = [
        'id' => $card['id'],
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
      for ($k = 0; $k < Globals::getCardsHandLimit(); $k++) {
        $card = array_pop($startingCards);
        $values[] = [
          'id' => $card['id'],
          'state' => 0,
          'rotation' => $player->getRotation(),
        ];
      }
      static::create($values, LOCATION_HAND . '-' . $player->getId());
    }
  }

  private static function getFromDeck(int $deck): array
  {
    return array_filter(Utils::populateWithIds(static::$allCards), fn($card) => $card['deck'] === $deck);
  }

  public static function getRemaining(): array
  {
    $remaining = [];
    $all = self::getAll()->filter(fn($card) => $card->getLocation() === LOCATION_DECK);
    $allFromMaterials = Utils::populateWithIds(static::$allCards);
    /** @var Card $card */
    foreach ($all as $card) {
      $cardDeck = $allFromMaterials[$card->getId()]['deck'];
      $cardType = $allFromMaterials[$card->getId()]['type'] ?? CARD_TYPE_SAVANNA;
      if ($cardDeck !== CARD_DECK_STARTING) {
        if (!isset($remaining[$cardDeck])) {
          $remaining[$cardDeck] = ['savanna' => 0, 'matriarch' => 0, 'lion' => 0];
        }
        if ($cardType === CARD_TYPE_MATRIARCH) {
          $remaining[$cardDeck]['matriarch'] += 1;
        } else if ($cardType === CARD_TYPE_LION) {
          $remaining[$cardDeck]['lion'] += 1;
        } else {
          $remaining[$cardDeck]['savanna'] += 1;
        }
      }
    }
    return $remaining;
  }

  public static function get(int $id, bool $raiseExceptionIfNotEnough = true): Card
  {
    return parent::get($id, $raiseExceptionIfNotEnough);
  }

  public static function getAtSquare(int $x, int $y): ?Card
  {
    return self::getInLocation(LOCATION_BOARD)->filter(fn($card) => $card->getX() == $x && $card->getY() == $y)->first();
  }

  public static function placeOnBoard(int $cardId, int $x, int $y, int $rotation): void
  {
    $card = Cards::getSingle($cardId);
    $card->setLocation(LOCATION_BOARD);
    $card->setX($x);
    $card->setY($y);
    $card->setRotation($rotation);
  }
}
