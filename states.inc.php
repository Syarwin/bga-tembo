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
 * states.inc.php
 *
 * Tembo game states description
 *
 */

require_once "modules/php/constants.inc.php";

$machinestates = [
  // The initial state. Please do not modify.
  ST_GAME_SETUP => [
    'name' => 'gameSetup',
    'description' => '',
    'type' => 'manager',
    'action' => 'stGameSetup',
    'transitions' => ['' => ST_SETUP_BRANCH],
  ],

  ST_GENERIC_NEXT_PLAYER => [
    'name' => 'genericNextPlayer',
    'type' => 'game',
  ],

  //////////////////////////////////
  //  ____       _
  // / ___|  ___| |_ _   _ _ __
  // \___ \ / _ \ __| | | | '_ \
  //  ___) |  __/ |_| |_| | |_) |
  // |____/ \___|\__|\__,_| .__/
  //                      |_|
  //////////////////////////////////

  ST_SETUP_BRANCH => [
    'name' => 'setupBranch',
    'description' => '',
    'type' => 'game',
    'action' => 'stSetupBranch',
    'transitions' => [
      '' => ST_SITTING_AROUND_TABLE,
    ],
  ],

  ST_SITTING_AROUND_TABLE => [
    'name' => 'sittingAroundTable',
    'description' => clienttranslate('${actplayer} must choose the direction of play for the entire game'),
    'descriptionmyturn' => clienttranslate('${you} must choose your direction of play for the entire game'),
    'type' => 'multipleactiveplayer',
    'action' => 'stSittingAroundTable',
    'possibleactions' => ['actSittingAroundTable', 'actChangedMind'],
    'transitions' => ['' => ST_TURN_BOARD_TILE],
  ],

  ST_TURN_BOARD_TILE => [
    'name' => 'turnBoardTile',
    'description' => clienttranslate(
      'All players collectively must decide if they want to pay 2 energy to re-orientate one Savanna board'
    ),
    'descriptionmyturn' => clienttranslate('${you} must decide if you want to pay 2 energy to re-orientate one Savanna board'),
    'type' => 'multipleactiveplayer',
    'action' => 'stMakeEveryoneActive',
    'possibleactions' => ['actReorientBoardTile', 'actLeaveBoardTiles'],
    'transitions' => ['' => ST_SETUP_CARDS],
  ],

  ST_SETUP_CARDS => [
    'name' => 'setupCards',
    'description' => '',
    'type' => 'game',
    'action' => 'stSetupCards',
    'transitions' => [
      'debug' => ST_BEFORE_START_OF_GAME,
    ],
  ],

  ST_DUMMY => [
    'name' => 'dummyState',
    'description' => 'FOO',
    'descriptionmyturn' => 'FOO',
    'type' => 'activeplayer',
  ],


  ST_BEFORE_START_OF_GAME => [
    'name' => 'beforeStartOfGame',
    'description' => '',
    'type' => 'game',
    'action' => 'stBeforeStartOfGame',
    'updateGameProgression' => true,
  ],

  //////////////////////////////
  //  _____
  // |_   _|   _ _ __ _ __
  //   | || | | | '__| '_ \
  //   | || |_| | |  | | | |
  //   |_| \__,_|_|  |_| |_|
  //////////////////////////////

  ST_TURN_ACTION => [
    'name' => 'turnAction',
    'description' => '',
    'type' => 'game',
    'action' => 'stTurnAction',
    'updateGameProgression' => true,
  ],

  ////////////////////////////////////
  //  _____             _
  // | ____|_ __   __ _(_)_ __   ___
  // |  _| | '_ \ / _` | | '_ \ / _ \
  // | |___| | | | (_| | | | | |  __/
  // |_____|_| |_|\__, |_|_| |_|\___|
  //              |___/
  ////////////////////////////////////
  ST_RESOLVE_STACK => [
    'name' => 'resolveStack',
    'type' => 'game',
    'action' => 'stResolveStack',
    'transitions' => [],
  ],

  ST_CONFIRM_TURN => [
    'name' => 'confirmTurn',
    'description' => clienttranslate('${actplayer} must confirm or restart their turn'),
    'descriptionmyturn' => clienttranslate('${you} must confirm or restart your turn'),
    'type' => 'activeplayer',
    'args' => 'argsConfirmTurn',
    'action' => 'stConfirmTurn',
    'possibleactions' => ['actConfirmTurn', 'actRestart'],
    'transitions' => [],
  ],

  ST_CONFIRM_PARTIAL_TURN => [
    'name' => 'confirmPartialTurn',
    'description' => clienttranslate('${actplayer} must confirm the switch of player'),
    'descriptionmyturn' => clienttranslate(
      '${you} must confirm the switch of player. You will not be able to restart turn'
    ),
    'type' => 'activeplayer',
    'args' => 'argsConfirmTurn',
    'action' => 'stConfirmPartialTurn',
    'possibleactions' => ['actConfirmPartialTurn', 'actRestart'],
  ],

  ST_RESOLVE_CHOICE => [
    'name' => 'resolveChoice',
    'description' => clienttranslate('${actplayer} must choose which effect to resolve'),
    'descriptionmyturn' => clienttranslate('${you} must choose which effect to resolve'),
    'descriptionxor' => clienttranslate('${actplayer} must choose exactly one effect'),
    'descriptionmyturnxor' => clienttranslate('${you} must choose exactly one effect'),
    'type' => 'activeplayer',
    'args' => 'argsResolveChoice',
    'action' => 'stResolveChoice',
    'possibleactions' => ['actChooseAction', 'actPassOptionalAction', 'actRestart'],
    'transitions' => [],
  ],

  ST_IMPOSSIBLE_MANDATORY_ACTION => [
    'name' => 'impossibleAction',
    'description' => clienttranslate(
      '${actplayer} can\'t take the mandatory action and must restart his turn or exchange/cook'
    ),
    'descriptionmyturn' => clienttranslate(
      '${you} can\'t take the mandatory action. Restart your turn or exchange/cook to make it possible'
    ),
    'type' => 'activeplayer',
    'args' => 'argsImpossibleAction',
    'possibleactions' => ['actRestart'],
  ],

  ST_CONFIRM_END_OF_TURN => [
    'name' => 'confirmEndOfTurn',
    'description' => clienttranslate('${actplayer} must confirm or restart their turn'),
    'descriptionmyturn' => clienttranslate('${you} must confirm or restart your turn'),
    'type' => 'activeplayer',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actConfirmEndOfTurn', 'actRestart'],
  ],

  ST_GENERIC_AUTOMATIC => [
    'name' => "genericAutomatic",
    'descriptionmyturn' => "",
    'type' => "private",
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction'
  ],

  ////////////////////////////////////////////////////////////////////////////
  //     _   _                  _         _        _   _
  //    / \ | |_ ___  _ __ ___ (_) ___   / \   ___| |_(_) ___  _ __  ___
  //   / _ \| __/ _ \| '_ ` _ \| |/ __| / _ \ / __| __| |/ _ \| '_ \/ __|
  //  / ___ \ || (_) | | | | | | | (__ / ___ \ (__| |_| | (_) | | | \__ \
  // /_/   \_\__\___/|_| |_| |_|_|\___/_/   \_\___|\__|_|\___/|_| |_|___/
  //
  ////////////////////////////////////////////////////////////////////////////

  ST_USE_CARD => [
    'name' => 'useCard',
    'description' => clienttranslate('${actplayer} must choose to build savanna or to place elephants'),
    'descriptionmyturn' => clienttranslate('${you} must choose to build savanna or to place elephants'),
    'descriptionmatriarch' => clienttranslate('${actplayer} must choose to build savanna, to place elephants or to play their matriarch card'),
    'descriptionmyturnmatriarch' => clienttranslate('${you} must choose to build savanna, to place elephants or to play your matriarch card'),
    'type' => 'activeplayer',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'possibleactions' => [
      'actRestart',
      'actPlaceCard',
      'actPlaceElephants',
      'actPlaceSingleElephant',
      'actUseSupportToken',
      'actPlayMatriarch',
      'actLeaveMatriarch',
    ],
  ],

  ST_PLAYER_GAIN_LOSE_ELEPHANTS => [
    'name' => 'playerGainLoseElephants',
    'description' => clienttranslate('${actplayer} must choose a player who will GAIN ${amount} elephants'),
    'descriptionmyturn' => clienttranslate('${you} must choose a player who will GAIN ${amount} elephants'),
    'descriptionlose' => clienttranslate('${actplayer} must choose a player who will LOSE ${amount} elephants'),
    'descriptionmyturnlose' => clienttranslate('${you} must choose a player who will LOSE ${amount} elephants'),
    'type' => 'activeplayer',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actRestart', 'actChoosePlayerGainLoseElephants'],
  ],

  ST_PLACE_SINGLE_ELEPHANT => [
    'name' => 'placeSingleElephant',
    'description' => clienttranslate('${actplayer} must place a single elephant ignoring rough terrain'),
    'descriptionmyturn' => clienttranslate('${you} must place a single elephant ignoring rough terrain'),
    'type' => 'activeplayer',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actRestart', 'actPlaceSingleElephant'],
  ],

  ST_MATRIARCH => [
    'name' => 'playMatriarch',
    'description' => clienttranslate('${actplayer} must decide to move the Matriarch or not'),
    'descriptionmyturn' => clienttranslate('${you} must decide to move the Matriarch or not'),
    'type' => 'activeplayer',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actRestart', 'actPlayMatriarch', 'actLeaveMatriarch'],
  ],

  ST_DISCARD_SECOND_MATRIARCH => [
    'name' => 'discardSecondMatriarch',
    'description' => clienttranslate('${actplayer} may discard one Matriarch to avoid triggering a Double Matriarch (once per game)'),
    'descriptionmyturn' => clienttranslate('${you} may discard one Matriarch to avoid triggering a Double Matriarch (once per game)'),
    'type' => 'activeplayer',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actDiscardSecondMatriarch', 'actDoNotDiscardSecondMatriarch'],
  ],

  //////////////////////////////////////////////////////////////////
  //  _____           _    ___   __    ____
  // | ____|_ __   __| |  / _ \ / _|  / ___| __ _ _ __ ___   ___
  // |  _| | '_ \ / _` | | | | | |_  | |  _ / _` | '_ ` _ \ / _ \
  // | |___| | | | (_| | | |_| |  _| | |_| | (_| | | | | | |  __/
  // |_____|_| |_|\__,_|  \___/|_|    \____|\__,_|_| |_| |_|\___|
  //////////////////////////////////////////////////////////////////

  ST_PRE_END_OF_GAME => [
    'name' => 'preEndOfGame',
    // 'type' => 'activeplayer',
    // 'description' => clienttranslate('END OF GAME'),
    // 'descriptionmyturn' => clienttranslate('END OF GAME'),
    'type' => 'game',
    'action' => 'stPreEndOfGame',
    'transitions' => ['' => ST_END_GAME],
  ],

  // Final state.
  // Please do not modify (and do not overload action/args methods).
  ST_END_GAME => [
    'name' => 'gameEnd',
    'description' => clienttranslate('End of game'),
    'type' => 'manager',
    'action' => 'stGameEnd',
    'args' => 'argGameEnd',
  ],
];
