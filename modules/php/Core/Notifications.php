<?php

namespace Bga\Games\Tembo\Core;

use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\Game;

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
