<?php

const EVENTS = [
  // row 1 of event-cards.jpg
  0 => [
    'type' => EVENT_TYPE_PERSISTENT,
    'effect' => EVENT_EFFECT_TREE_2_ENERGY,
    'arg' => TREE_TEAL,
  ],
  1 => [
    'type' => EVENT_TYPE_PERSISTENT,
    'effect' => EVENT_EFFECT_TREE_2_ENERGY,
    'arg' => TREE_BROWN,
  ],
  2 => [
    'type' => EVENT_TYPE_IMMEDIATE,
    'effect' => EVENT_EFFECT_TREE_LAY,
    'arg' => TREE_TEAL,
  ],
  3 => [
    'type' => EVENT_TYPE_IMMEDIATE,
    'effect' => EVENT_EFFECT_TREE_LAY,
    'arg' => TREE_GREEN,
  ],

  // row 2
  4 => [
    'type' => EVENT_TYPE_IMMEDIATE,
    'effect' => EVENT_EFFECT_TREE_LAY,
    'arg' => TREE_BROWN,
  ],
  5 => [
    'type' => EVENT_TYPE_PERSISTENT,
    'effect' => EVENT_EFFECT_OASIS,
    'arg' => 5,
  ],
  6 => [
    'type' => EVENT_TYPE_IMMEDIATE,
    'effect' => EVENT_EFFECT_LOSE_2_ELEPHANTS,
  ],
  7 => [
    'type' => EVENT_TYPE_PERSISTENT,
    'effect' => EVENT_EFFECT_ROUGH_TERRAIN,
    'arg' => 1,
  ],

  // row 3
  8 => [
    'type' => EVENT_TYPE_PERSISTENT,
    'effect' => EVENT_EFFECT_OASIS,
    'arg' => 0,
  ],
  9 => [
    'type' => EVENT_TYPE_PERSISTENT,
    'effect' => EVENT_EFFECT_DO_NOT_IGNORE_ROUGH_TERRAIN,
  ],
  10 => [
    'type' => EVENT_TYPE_IMMEDIATE,
    'effect' => EVENT_EFFECT_LOSE_SUPPORT,
  ],
  11 => [
    'type' => EVENT_TYPE_IMMEDIATE,
    'effect' => EVENT_EFFECT_LION_ACTIVATES,
    'arg' => LIONESS,
  ],

  // row 4
  12 => [
    'type' => EVENT_TYPE_IMMEDIATE,
    'effect' => EVENT_EFFECT_LION_ACTIVATES,
    'arg' => LION,
  ],
  13 => [
    'type' => EVENT_TYPE_IMMEDIATE,
    'effect' => EVENT_EFFECT_TREE_LAY,
    'arg' => TREE_RED,
  ],
  14 => [
    'type' => EVENT_TYPE_IMMEDIATE,
    'effect' => EVENT_EFFECT_ELEPHANT_DIES,
  ],
  15 => [
    'type' => EVENT_TYPE_IMMEDIATE,
    'effect' => EVENT_EFFECT_LIONS_LAY
  ],

  // row 5
  16 => [
    'type' => EVENT_TYPE_PERSISTENT,
    'effect' => EVENT_EFFECT_ROUGH_TERRAIN,
    'arg' => 3,
  ],
  17 => [
    'type' => EVENT_TYPE_IMMEDIATE,
    'effect' => EVENT_EFFECT_ENERGY,
  ],
  18 => [
    'type' => EVENT_TYPE_PERSISTENT,
    'effect' => EVENT_EFFECT_TREE_2_ENERGY,
    'arg' => TREE_RED,
  ],
  19 => [
    'type' => EVENT_TYPE_PERSISTENT,
    'effect' => EVENT_EFFECT_NO_CARD_ROTATION,
  ]
];
