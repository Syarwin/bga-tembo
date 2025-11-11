<?php

namespace Bga\Games\Tembo\Core;

use Bga\Games\Tembo\Game;
use Bga\Games\Tembo\Models\Card;
use Bga\Games\Tembo\Models\Player;

class Notifications
{
  protected static $listeners = [];

  protected static $cachedValues = [];

  public static function resetCache()
  {
    foreach (self::$listeners as $listener) {
      $method = $listener['method'];
      self::$cachedValues[$listener['name']] = call_user_func($method);
    }
  }

  public static function updateIfNeeded()
  {
    foreach (self::$listeners as $listener) {
      $name = $listener['name'];
      $method = $listener['method'];
      $val = call_user_func($method);
      if ($val !== self::$cachedValues[$name]) {
        self::$cachedValues[$name] = $val;
        Game::get()->notify->all('updateInformations', '', [
          $name => $val,
        ]);
      }
    }
  }

  /*************************
   **** GENERIC METHODS ****
   *************************/
  protected static function notifyAll($name, $msg, $data)
  {
    self::updateArgs($data);
    Game::get()->notify->all($name, $msg, $data);
    self::updateIfNeeded();
  }

  protected static function notify($player, $name, $msg, $data)
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::updateArgs($data);
    Game::get()->notify->player($pId, $name, $msg, $data);
  }

  public static function message($txt, $args = [])
  {
    self::notifyAll('mediumMessage', $txt, $args);
  }

  public static function longMessage($txt, $args = [])
  {
    self::notifyAll('longMessage', $txt, $args);
  }

  public static function messageTo($player, $txt, $args = [])
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::notify($pId, 'mediumMessage', $txt, $args);
  }

  public static function newUndoableStep($player, $stepId)
  {
    self::notify($player, 'newUndoableStep', clienttranslate('Undo here'), [
      'stepId' => $stepId,
      'preserve' => ['stepId'],
    ]);
  }

  public static function clearTurn($player, $notifIds)
  {
    self::notifyAll('clearTurn', clienttranslate('${player_name} restarts their turn'), [
      'player' => $player,
      'notifIds' => $notifIds,
    ]);
  }

  // Remove extra information from cards
  protected function filterCardDatas($card)
  {
    return [
      'id' => $card['id'],
      'location' => $card['location'],
      'pId' => $card['pId'],
    ];
  }

  public static function refreshUI($datas)
  {
    // // Keep only the thing that matters
    $fDatas = [
      'players' => $datas['players'],
      'meeples' => $datas['meeples'],
    ];

    self::notifyAll('refreshUI', '', [
      'datas' => $fDatas,
    ]);
  }

  public static function refreshHand($player, $hand)
  {
    foreach ($hand as &$card) {
      $card = self::filterCardDatas($card);
    }
    self::notify($player, 'refreshHand', '', [
      'player' => $player,
      'hand' => $hand,
    ]);
  }

  /////////////////////////////////
  //  ____       _
  // / ___|  ___| |_ _   _ _ __
  // \___ \ / _ \ __| | | | '_ \
  //  ___) |  __/ |_| |_| | |_) |
  // |____/ \___|\__|\__,_| .__/
  //                      |_|
  /////////////////////////////////

  public static function boardTileRotated(int $id, int $rotation)
  {
    self::notifyAll('boardTileRotated', '', [
      'id' => $id,
      'rotation' => $rotation,
    ]);
  }

  public static function energyDecreased(int $energy, int $delta): void
  {
    self::notifyAll('energyChanged', clienttranslate('You lose ${delta} energy and now it is at ${energy}'), [
      'energy' => $energy,
      'delta' => $delta,
    ]);
  }

  public static function energyIncreased(int $energy, int $delta, string $msg = null, array $args = []): void
  {
    if (is_null($msg)) {
      $msg = clienttranslate('You gain ${delta} energy and now it is at ${energy}');
    }
    self::notifyAll('energyChanged', $msg, [
      'energy' => $energy,
      'delta' => $delta,
      ...$args,
    ]);
  }

  public static function elephantsGained(Player $player, array $gained, string $msg = null)
  {
    if (is_null($msg)) {
      $msg = clienttranslate('${player_name} gains ${gainedAmount} elephants');
    }
    self::notifyAll('elephantsGained', $msg, [
      'player' => $player,
      'gained' => $gained,
      'gainedAmount' => count($gained),
    ]);
  }

  public static function elephantsLost(Player $player, array $lost, string $msg = null)
  {
    if (is_null($msg)) {
      $msg = clienttranslate('${player_name} loses ${lostAmount} elephants');
    }
    self::notifyAll('elephantsLost', $msg, [
      'player' => $player,
      'lost' => $lost,
      'lostAmount' => count($lost),
    ]);
  }

  public static function cardPlacedOnBoard(Player $player, Card $card)
  {
    self::notifyAll('cardPlacedOnBoard', clienttranslate('${player_name} places a card'), [
      'player' => $player,
      'card' => $card,
    ]);
  }

  public static function cardsDrawn(Player $player, array $cards)
  {
    self::notifyAll('cardsDrawn', '', [
      'player' => $player,
      'cards' => $cards,
    ]);
  }

  public static function elephantsPlaced(Player $player, array $elephants, ?int $cardId)
  {
    $msg = clienttranslate('${player_name} places ${amount} elephant(s) on the board');
    self::notifyAll('elephantsPlaced', $msg, [
      'player' => $player,
      'elephants' => $elephants,
      'amount' => count($elephants),
      'cardId' => $cardId,
    ]);
  }

  public static function treesEaten(Player $player, int $color, int $energyAmount)
  {
    $colorName = [
      SPACE_TREE_GREEN => clienttranslate('green'),
      SPACE_TREE_RED => clienttranslate('red'),
      SPACE_TREE_BROWN => clienttranslate('brown'),
      SPACE_TREE_TEAL => clienttranslate('teal'),
    ][$color];
    if ($energyAmount === 0) {
      $msg = clienttranslate('Elephants placed by ${player_name} eat ${color} trees but the matching color standee is already laying on its side, nothing happened');
    } else {
      $msg = clienttranslate('Elephants placed by ${player_name} eat ${color} trees and gain ${amount} energy');
    }
    self::notifyAll('treesEaten', $msg, [
      'player' => $player,
      'color' => $colorName,
      'amount' => $energyAmount,
      'i18n' => ['color'],
    ]);
  }

  public static function landmarkVisited(string $landMark)
  {
    $landMarkName = [
      LANDMARK_SNOW => clienttranslate('Snow'),
      LANDMARK_MEADOW => clienttranslate('Meadow'),
      LANDMARK_RIVER => clienttranslate('River'),
      LANDMARK_ROCKS => clienttranslate('Rocks'),
      LANDMARK_CANYON => clienttranslate('Canyon'),
      LANDMARK_WATERFALL => clienttranslate('Waterfall'),
    ][$landMark];
    $msg = clienttranslate('Elephants have just visited the ${landmarkName} landmark!');
    self::notifyAll('landmarkVisited', $msg, [
      'landmark' => $landMark,
      'landmarkName' => $landMarkName,
      'i18n' => ['landmarkName'],
    ]);
  }

  public static function matriarchAction(Player $player, array $cards)
  {
    $msg = clienttranslate('${player_name} uses the Matriarch action. All players return their elephants from the board and all Tree standees are stood back upright');
    self::notify($player, 'cardsDiscarded', '', ['cards' => $cards]);
    self::notifyAll('matriarchAction', $msg, [
      'player' => $player,
      // Any more args?
    ]);
  }

  ///////////////////////////////////////////////////////////////
  //  _   _           _       _            _
  // | | | |_ __   __| | __ _| |_ ___     / \   _ __ __ _ ___
  // | | | | '_ \ / _` |/ _` | __/ _ \   / _ \ | '__/ _` / __|
  // | |_| | |_) | (_| | (_| | ||  __/  / ___ \| | | (_| \__ \
  //  \___/| .__/ \__,_|\__,_|\__\___| /_/   \_\_|  \__, |___/
  //       |_|                                      |___/
  ///////////////////////////////////////////////////////////////

  /*
   * Automatically adds some standard field about player and/or card
   */
  protected static function updateArgs(&$data)
  {
    if (isset($data['player'])) {
      $data['player_name'] = $data['player']->getName();
      $data['player_id'] = $data['player']->getId();
      unset($data['player']);
    }

    // if (isset($data['card'])) {
    //   $data['card_id'] = $data['card']->getId();
    //   $data['card_name'] = $data['card']->getName();
    //   $data['i18n'][] = 'card_name';
    //   $data['preserve'][] = 'card_id';
    // }

    // if (isset($data['cards'])) {
    //   $args = [];
    //   $logs = [];
    //   foreach ($data['cards'] as $i => $card) {
    //     $logs[] = '${card_name_' . $i . '}';
    //     $args['i18n'][] = 'card_name_' . $i;
    //     $args['card_name_' . $i] = [
    //       'log' => '${card_name}',
    //       'args' => [
    //         'i18n' => ['card_name'],
    //         'card_name' => is_array($card) ? $card['name'] : $card->getName(),
    //         'card_id' => is_array($card) ? $card['id'] : $card->getId(),
    //         'preserve' => ['card_id'],
    //       ],
    //     ];
    //   }
    //   $data['card_names'] = [
    //     'log' => join(', ', $logs),
    //     'args' => $args,
    //   ];
    //   $data['i18n'][] = 'card_names';
    // }
  }
}
