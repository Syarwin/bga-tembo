<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Tembo implementation : © Timothée (Tisaac) Pecatte <tim.pecatte@gmail.com>, Pavel Kulagin (KuWizard) <kuzwiz@mail.ru>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * tembo.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

namespace Bga\Games\Tembo;

use Bga\Games\Tembo\Core\Globals;
use Bga\Games\Tembo\Managers\FlowerCards;
use Bga\Games\Tembo\Managers\Players;
use Bga\Games\Tembo\States\SanityTrait;
use Bga\Games\Tembo\States\TurnTrait;
use Bga\Games\Tembo\Core\Stats;
use Bga\Games\Tembo\Managers\Meeples;
use Bga\Games\Tembo\Models\Board;

require_once dirname(__FILE__) . "/../php/Materials/Ecosystems.php";
require_once APP_GAMEMODULE_PATH . 'module/table/table.game.php';

class Game extends \Table
{
  use DebugTrait;
  use TurnTrait;
  use SanityTrait;

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

  protected function getGameName()
  {
    return 'tembo';
  }

  /*
   * setupNewGame:
   */
  protected function setupNewGame($players, $options = [])
  {
    Stats::setupNewGame();
    Players::setupNewGame($players, $options);
    Globals::setupNewGame($players, $options);
    FlowerCards::setupNewGame();
    Meeples::setupNewGame();
    $this->activeNextPlayer();
  }

  /*
   * getAllDatas:
   */
  public function getAllDatas(): array
  {
    $board = new Board(null);
    $isSolo = Globals::isSolo();
    $data = [
      'board' => [
        'ids' => Globals::getBoards(),
        'waterSpaces' => $board->getWaterSpaces(),
        'zones' => $board->getZones(),
        'cellsZone' => $board->getCellsZone(),
      ],
      'meeples' => Meeples::getUiData(),
      'flowerCards' => FlowerCards::getUiData(),
      'players' => Players::getUiData(),

      'turn' => Globals::getTurn(),
      'endGameText' => $isSolo ? Globals::getEndGameText() : null,
      'endGameStars' => $isSolo ? Globals::getEndGameStars() : null,
    ];
    $ecosystemsIds = Globals::getEcosystems();
    if ($ecosystemsIds) {
      $ecosystems = [];
      foreach ($ecosystemsIds as $ecosystemId) {
        $ecosystems[$ecosystemId] = ECOSYSTEMS[$ecosystemId];
      }
      $data['ecosystemsTexts'] = $ecosystems;
    }
    if (!$isSolo) {
      $data['pangolin'] = Globals::getPangolinLocation();
    }
    return $data;
  }

  /*
   * getGameProgression:
   */
  function getGameProgression()
  {
    if (Globals::isSolo()) {
      return 100 * (Globals::getTurn() - 1) / 18;
    } else {
      $pIds = $this->getRemainingPlayersToPlay();
      $nPlayers = Players::count();
      return 100 * (2 * $nPlayers * Globals::getTurn() - count($pIds)) / (2 * $nPlayers * 9);
    }
  }

  ///////////////////////////
  //// DEBUG FUNCTIONS //////
  ///////////////////////////

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

  public static function a()
  {
    // Method to debug something. Just type "a()" in the table chat
    var_dump(Cards::get(2));
  }
}
