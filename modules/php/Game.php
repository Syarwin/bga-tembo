<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Tembo implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */

declare(strict_types=1);

namespace Bga\Games\Tembo;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\States\EngineTrait;
use Bga\Games\Tembo\States\SetupTrait;

class Game extends \Bga\GameFramework\Table
{
  use SetupTrait;
  use DebugTrait;
  use EngineTrait;

  public static $instance = null;

  function __construct()
  {
    parent::__construct();
    self::$instance = $this;
    self::initGameStateLabels([]);
  }

  public static function get()
  {
    return self::$instance;
  }

  /*
   * getAllDatas:
   */
  public function getAllDatas(): array
  {
    $data = [
      'players' => Players::getUiData(),
    ];

    return $data;
  }

  /*
   * getGameProgression:
   */
  function getGameProgression(): int
  {
    return 0;
  }

  ///////////////////////////////////////////////
  ///////////////////////////////////////////////
  ////////////   Custom Turn Order   ////////////
  ///////////////////////////////////////////////
  ///////////////////////////////////////////////
  public function initCustomTurnOrder(string $key, ?array $order, mixed $callback, mixed $endCallback, bool $loop = false, bool $autoNext = true, array $args = []): void
  {
    $turnOrders = Globals::getCustomTurnOrders();
    $turnOrders[$key] = [
      'order' => $order ?? Players::getTurnOrder(),
      'index' => -1,
      'callback' => $callback,
      'args' => $args, // Useful mostly for auto card listeners
      'endCallback' => $endCallback,
      'loop' => $loop,
    ];
    Globals::setCustomTurnOrders($turnOrders);

    if ($autoNext) {
      $this->nextPlayerCustomOrder($key);
    }
  }

  public function initCustomDefaultTurnOrder(string $key, mixed $callback, mixed $endCallback, bool $loop = false, bool $autoNext = true): void
  {
    $this->initCustomTurnOrder($key, null, $callback, $endCallback, $loop, $autoNext);
  }

  public function nextPlayerCustomOrder(string $key): void
  {
    $turnOrders = Globals::getCustomTurnOrders();
    if (!isset($turnOrders[$key])) {
      throw new \BgaVisibleSystemException('Asking for the next player of a custom turn order not initialized : ' . $key);
    }

    // Increase index and save
    $o = $turnOrders[$key];
    $i = $o['index'] + 1;
    if ($i == count($o['order']) && $o['loop']) {
      $i = 0;
    }
    $turnOrders[$key]['index'] = $i;
    Globals::setCustomTurnOrders($turnOrders);

    if ($i < count($o['order'])) {
      $this->gamestate->jumpToState(ST_GENERIC_NEXT_PLAYER);
      $this->gamestate->changeActivePlayer($o['order'][$i]);
      $this->jumpToOrCall($o['callback'], $o['args']);
    } else {
      $this->endCustomOrder($key);
    }
  }

  public function endCustomOrder(string $key): void
  {
    $turnOrders = Globals::getCustomTurnOrders();
    if (!isset($turnOrders[$key])) {
      throw new \BgaVisibleSystemException('Asking for ending a custom turn order not initialized : ' . $key);
    }

    $o = $turnOrders[$key];
    $turnOrders[$key]['index'] = count($o['order']);
    Globals::setCustomTurnOrders($turnOrders);
    $callback = $o['endCallback'];
    $this->jumpToOrCall($callback);
  }

  public function jumpToOrCall(int|string|array $mixed, $args = [])
  {
    if (is_int($mixed)) {
      $this->gamestate->jumpToState($mixed);
    } elseif (method_exists($this, $mixed)) {
      $method = $mixed;
      $this->$method($args);
    } else {
      throw new \BgaVisibleSystemException('Failing to jumpToOrCall  : ' . $mixed);
    }
  }


  ////////////////////////////////////
  ////////////   Zombie   ////////////
  ////////////////////////////////////
  /*
   * zombieTurn:
   *   This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
   *   You can do whatever you want in order to make sure the turn of this player ends appropriately
   */
  public function zombieTurn($state, $active_player): void
  {
    switch ($state['name']) {
        // TODO
    }
  }

  /////////////////////////////////////
  //////////   DB upgrade   ///////////
  /////////////////////////////////////
  // You don't have to care about this until your game has been published on BGA.
  // Once your game is on BGA, this method is called everytime the system detects a game running with your old Database scheme.
  // In this case, if you change your Database scheme, you just have to apply the needed changes in order to
  //   update the game database and allow the game to continue to run with your new version.
  /////////////////////////////////////
  /*
   * upgradeTableDb
   *  - int $from_version : current version of this game database, in numerical form.
   *      For example, if the game was running with a release of your game named "140430-1345", $from_version is equal to 1404301345
   */
  public function upgradeTableDb($from_version)
  {
    //        if ($from_version <= 2412211311) {
    //            $this->updateDBTableCustom();
    //        }
  }

  function updateDBTableCustom()
  {
    // This method is used as a workaround to update DB after some new fields appeared
  }

  /////////////////////////////////////////////////////////////
  // Exposing protected methods, please use at your own risk //
  /////////////////////////////////////////////////////////////

  // Exposing protected method getCurrentPlayerId
  public static function getCurrentPId()
  {
    return self::get()->getCurrentPlayerId();
  }

  // Exposing protected method translation
  public static function translate($text)
  {
    return self::_($text);
  }
}
