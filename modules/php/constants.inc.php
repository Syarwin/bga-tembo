<?php
require_once 'gameoptions.inc.php';

/*
 * State constants
 */
const ST_GAME_SETUP = 1;

const ST_SETUP_BRANCH = 2;
const ST_DUMMY = 3;
const ST_BEFORE_START_OF_GAME = 6;
const ST_TURN_ACTION = 7;


const ST_RESOLVE_STACK = 90;
const ST_RESOLVE_CHOICE = 91;
const ST_IMPOSSIBLE_MANDATORY_ACTION = 92;
const ST_CONFIRM_TURN = 93;
const ST_CONFIRM_PARTIAL_TURN = 94;
const ST_CONFIRM_END_OF_TURN = 95; // NEEDED FOR OBJECTIVE VALIDATION

const ST_GENERIC_NEXT_PLAYER = 97;
const ST_PRE_END_OF_GAME = 98;
const ST_END_GAME = 99;

/*
 * ENGINE
 */
const NODE_SEQ = 'seq';
const NODE_OR = 'or';
const NODE_XOR = 'xor';
const NODE_PARALLEL = 'parallel';
const NODE_LEAF = 'leaf';
const NODE_THEN_OR = 'thenOr';
const PRE_ACTION_DONE = 'preActionDone';

const ZOMBIE = 98;
const PASS = 99;

/*
 * Atomic action
 */

const CHOOSE_ACTION = 'ChooseAction';

/*
 * Board tiles
 */
// Names are for readability. A name reflects a landmark purple spaces shape
// Numbers reflect tiles from the rulebook, p.3, top to bottom, left to right
const BOARD_TILE_DIAGONAL_CANYON = 0;
const BOARD_TILE_L_SHAPED_RIVER = 1;
const BOARD_TILE_DIAGONAL_MEADOW = 2;
const BOARD_TILE_CORNER_WATERFALL = 3;
const BOARD_TILE_V_ROCKS = 4;
const BOARD_TILE_SINGLE_SNOW = 5;
const ALL_BOARD_TILES = [
  BOARD_TILE_DIAGONAL_CANYON,
  BOARD_TILE_L_SHAPED_RIVER,
  BOARD_TILE_DIAGONAL_MEADOW,
  BOARD_TILE_CORNER_WATERFALL,
  BOARD_TILE_V_ROCKS,
  BOARD_TILE_SINGLE_SNOW
];

/*
 * Pre-defined cards references on board tiles
 */
const CARD_REF_DIAGONAL_CANYON = 0;
const CARD_REF_L_SHAPED_RIVER = 1;
const CARD_REF_DIAGONAL_MEADOW = 2;
const CARD_REF_CORNER_WATERFALL = 3;
const CARD_REF_V_ROCKS = 4;
const CARD_REF_SINGLE_SNOW = 5;

/*
 * Board tiles bonuses
 */
const BONUS_ALL_GAIN_2 = 0;
const BONUS_ANOTHER_GAINS_4 = 1;
const BONUS_YOU_5_ANOTHER_MINUS_2 = 2;
const BONUS_GAIN_3_PLACE_1_IGNORE_ROUGH = 3;

/*
 * Meeples
 */

/*
 * Resources
 */


/*
 * MISC
 */

const LOCATION_TABLE = 'table';
const INFINITY = 1000;


/******************
 ****** STATS ******
 ******************/
