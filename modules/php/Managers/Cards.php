<?php

namespace Bga\Games\Tembo\Managers;

use Bga\Games\Tembo\Helpers\CachedPieces;
use Bga\Games\Tembo\Helpers\Collection;
use Bga\Games\Tembo\Models\Card;

class FlowerCards extends CachedPieces
{
  protected static string $table = 'cards';
  protected static string $prefix = 'card_';
  protected static array $customFields = [];
  protected static null|Collection $datas = null;
  protected static bool $autoremovePrefix = false;
  protected static bool $autoIncrement = true;

//  private static array $allCards = CARDS;

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
    // shuffle(self::$allCards);

    // $isSolo = Globals::isSolo();
    // $cardsInDeck = $isSolo ? 3 : (Players::count() * 2) - 1;
    // // 18 or 9 stacks / decks
    // $amountOfDecks = $isSolo ? 18 : 9;
    // for ($i = 1; $i <= $amountOfDecks; $i++) {
    //   $values = [];
    //   for ($k = 0; $k < $cardsInDeck; $k++) {
    //     $card = array_pop(self::$allCards);
    //     $values[] = [
    //       'flower_a' => $card[0],
    //       'flower_b' => $card[1] ?? null,
    //       'flower_c' => $card[2] ?? null
    //     ];
    //   }
    //   self::create($values, LOCATION_DECK . $i);
    // }
  }


  public static function get(int $id, bool $raiseExceptionIfNotEnough = true): Card
  {
    return parent::get($id, $raiseExceptionIfNotEnough);
  }
}
