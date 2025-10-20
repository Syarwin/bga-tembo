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
    'transitions' => ['' => ST_PREPARE_MARKET],
  ],

  ST_PREPARE_MARKET => [
    'name' => 'prepareMarket',
    'description' => clienttranslate('Preparing market for the next round'),
    'type' => 'game',
    'action' => 'stPrepareMarket',
    'transitions' => ['' => ST_TURN],
    'updateGameProgression' => true,
  ],

  ST_TURN => [
    'name' => 'turn',
    'description' => clienttranslate('${actplayer} must choose a Flower card and place the corresponding Flowers'),
    'descriptionmyturn' => clienttranslate('${you} must choose a Flower card and place the corresponding Flowers'),
    'type' => 'activeplayer',
    'args' => 'argsTurn',
    'action' => 'stTurn',
    'possibleactions' => ['actTakeTurn', 'actPlanTurn', 'actCancelPlan'],
    'transitions' => ['' => ST_END_OF_TURN_CLEANUP],
    'updateGameProgression' => true,
  ],

  ST_END_OF_TURN_CLEANUP => [
    'name' => 'prepareMarket',
    'description' => clienttranslate('Cleaning up at the end of turn'),
    'type' => 'game',
    'action' => 'stEndOfTurnCleanup',
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
