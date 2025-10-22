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
