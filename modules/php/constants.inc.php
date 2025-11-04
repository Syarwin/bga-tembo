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
const ST_SETUP_CARDS = 11;
const ST_SITTING_AROUND_TABLE = 12;
const ST_TURN_BOARD_TILE = 13;

const ST_CHOOSE_ACTION = 20;

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
const BOARD_TILE_SINGLE_SNOW = 0;
const BOARD_TILE_DIAGONAL_MEADOW = 1;
const BOARD_TILE_L_SHAPED_RIVER = 2;
const BOARD_TILE_V_ROCKS = 3;
const BOARD_TILE_DIAGONAL_CANYON = 4;
const BOARD_TILE_CORNER_WATERFALL = 5;
const ALL_BOARD_TILES = [
  BOARD_TILE_SINGLE_SNOW,
  BOARD_TILE_DIAGONAL_MEADOW,
  BOARD_TILE_L_SHAPED_RIVER,
  BOARD_TILE_V_ROCKS,
  BOARD_TILE_DIAGONAL_CANYON,
  BOARD_TILE_CORNER_WATERFALL,
];

/*
 * Pre-defined cards references on board tiles
 */
const CARD_REF_SINGLE_SNOW = 0;
const CARD_REF_DIAGONAL_MEADOW = 1;
const CARD_REF_L_SHAPED_RIVER = 2;
const CARD_REF_V_ROCKS = 3;
const CARD_REF_DIAGONAL_CANYON = 4;
const CARD_REF_CORNER_WATERFALL = 5;
const ALL_CARD_REFS = [
  CARD_REF_SINGLE_SNOW,
  CARD_REF_DIAGONAL_MEADOW,
  CARD_REF_L_SHAPED_RIVER,
  CARD_REF_V_ROCKS,
  CARD_REF_DIAGONAL_CANYON,
  CARD_REF_CORNER_WATERFALL
];

/*
 * Board tiles bonuses
 */
const BONUS_ALL_GAIN_2 = 10;
const BONUS_ANOTHER_GAINS_4 = 11;
const BONUS_YOU_5_ANOTHER_MINUS_2 = 12;
const BONUS_GAIN_3_PLACE_1_IGNORE_ROUGH = 13;

/*
 * Cards
 */
const CARD_TYPE_SAVANNA = 'savanna';
const CARD_TYPE_LION = 'lion';
const CARD_TYPE_MATRIARCH = 'matriarch';

const CARD_DECK_STARTING = 0;
const CARD_DECK_FIRST = 1;
const CARD_DECK_SECOND = 2;
const CARD_DECK_THIRD = 3;
const CARD_DECK_SUPPORT = 4;

const SHAPE_DIAG_DOWN = 0;
const SHAPE_DIAG_UP = 1;
const SHAPE_DASH_VERT = 2;
const SHAPE_DASH_HOR = 3;
const SHAPE_LONG_DASH_VERT = 4;
const SHAPE_LONG_DASH_HOR = 5;
const SHAPE_L = 6;
const SHAPES_CELLS = [
  SHAPE_DIAG_DOWN => [[0, 0], [1, 1]],
  SHAPE_DIAG_UP => [[0, 0], [1, -1]],
  SHAPE_DASH_HOR => [[0, 0], [1, 0]],
  SHAPE_DASH_VERT => [[0, 0], [0, 1]],
  SHAPE_LONG_DASH_HOR => [[0, 0], [-1, 0], [1, 0]],
  SHAPE_LONG_DASH_VERT => [[0, 0], [0, -1], [0, 1]],
  SHAPE_L => [[0, 0], [-1, 0], [0, -1]],
];

/*
 * Spaces types
 */
const SPACE_NONE = 0;
const SPACE_NORMAL = 1;
const SPACE_OASIS = 2;
const SPACE_ROUGH = 3;
const SPACE_LANDMARK = 4;
const SPACE_TREE_GREEN = 10;
const SPACE_TREE_RED = 11;
const SPACE_TREE_BROWN = 12;
const SPACE_TREE_TEAL = 13;

/*
 * Meeples
 */

const TREE_GREEN = 'tree-green';
const TREE_RED = 'tree-red';
const TREE_BROWN = 'tree-brown';
const TREE_TEAL = 'tree-teal';

const LION = 'lion';
const LIONESS = 'lioness';
const ELEPHANT = 'elephant';
const MATRIARCH = 'matriarch';

const LANDMARK_CANYON = 'landmark-canyon';
const LANDMARK_RIVER = 'landmark-river';
const LANDMARK_MEADOW = 'landmark-meadow';
const LANDMARK_WATERFALL = 'landmark-waterfall';
const LANDMARK_ROCKS = 'landmark-rocks';
const LANDMARK_SNOW = 'landmark-snow';

const ALL_TREES = [TREE_GREEN, TREE_RED, TREE_BROWN, TREE_TEAL];
const ALL_LANDMARKS = [
  LANDMARK_CANYON,
  LANDMARK_RIVER,
  LANDMARK_MEADOW,
  LANDMARK_WATERFALL,
  LANDMARK_ROCKS,
  LANDMARK_SNOW
];

/*
 * Resources
 */


/*
 * MISC
 */

const LOCATION_TABLE = 'table';
const LOCATION_HAND = 'hand';
const LOCATION_RESERVE = 'reserve';
const LOCATION_DECK = 'deck';
const LOCATION_BOARD = 'board';
const LOCATION_BOARD_START = 'board-start';
const INFINITY = 1000;

const STATE_LAYING = 0;
const STATE_STANDING = 1;
const STATE_TIRED = 0;
const STATE_RESTED = 1;


/******************
 ****** STATS ******
 ******************/
