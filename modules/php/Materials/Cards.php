<?php

const CARDS = [
  0 => [
    'type' => CARD_TYPE_SAVANNA,
    'deck' => CARD_DECK_SUPPORT,
    'pattern' => [],
    'spaces' => [
      SPACE_TREE_TEAL,
      SPACE_NONE,
      SPACE_NORMAL,
      SPACE_NONE,
      SPACE_TREE_TEAL,
      SPACE_NONE,
      SPACE_ROUGH,
      SPACE_OASIS,
      SPACE_ROUGH,
    ]
  ],
  1 => ['type' => CARD_TYPE_SAVANNA, 'deck' => CARD_DECK_SUPPORT, 'pattern' => [], 'spaces' => []],
  2 => ['type' => CARD_TYPE_SAVANNA, 'deck' => CARD_DECK_SUPPORT, 'pattern' => [], 'spaces' => []],
  3 => ['type' => CARD_TYPE_SAVANNA, 'deck' => CARD_DECK_SUPPORT, 'pattern' => [], 'spaces' => []],
  4 => ['type' => CARD_TYPE_SAVANNA, 'deck' => CARD_DECK_SUPPORT, 'pattern' => [], 'spaces' => []],
  5 => ['type' => CARD_TYPE_SAVANNA, 'deck' => CARD_DECK_STARTING, 'pattern' => [], 'spaces' => []],
  // TODO: Add more
  // ...
  // 6-16 - Starting
  // 17-26 - Stage I
  // 27-37 - Stage II,
  // 38-49 - Stage III
  50 => ['type' => CARD_TYPE_MATRIARCH, 'deck' => CARD_DECK_FIRST],
  51 => ['type' => CARD_TYPE_MATRIARCH, 'deck' => CARD_DECK_FIRST],
  52 => ['type' => CARD_TYPE_MATRIARCH, 'deck' => CARD_DECK_FIRST],
  53 => ['type' => CARD_TYPE_MATRIARCH, 'deck' => CARD_DECK_FIRST],
  54 => ['type' => CARD_TYPE_LION, 'deck' => CARD_DECK_FIRST],
  55 => ['type' => CARD_TYPE_LION, 'deck' => CARD_DECK_FIRST],
  56 => ['type' => CARD_TYPE_LION, 'deck' => CARD_DECK_FIRST],
  57 => ['type' => CARD_TYPE_MATRIARCH, 'deck' => CARD_DECK_SECOND],
  58 => ['type' => CARD_TYPE_MATRIARCH, 'deck' => CARD_DECK_SECOND],
  59 => ['type' => CARD_TYPE_MATRIARCH, 'deck' => CARD_DECK_SECOND],
  60 => ['type' => CARD_TYPE_LION, 'deck' => CARD_DECK_SECOND],
  61 => ['type' => CARD_TYPE_LION, 'deck' => CARD_DECK_SECOND],
  62 => ['type' => CARD_TYPE_LION, 'deck' => CARD_DECK_SECOND],
  63 => ['type' => CARD_TYPE_LION, 'deck' => CARD_DECK_THIRD],
  64 => ['type' => CARD_TYPE_LION, 'deck' => CARD_DECK_THIRD],
  65 => ['type' => CARD_TYPE_LION, 'deck' => CARD_DECK_THIRD],
  66 => ['type' => CARD_TYPE_LION, 'deck' => CARD_DECK_THIRD],
  67 => ['type' => CARD_TYPE_MATRIARCH, 'deck' => CARD_DECK_THIRD],
  68 => ['type' => CARD_TYPE_MATRIARCH, 'deck' => CARD_DECK_THIRD],
];

const BOARD_SPACES = [
  BOARD_TILE_DIAGONAL_CANYON => [
    SPACE_LANDMARK,
    SPACE_NONE,
    SPACE_ROUGH,
    SPACE_LANDMARK,
    SPACE_NONE,
    SPACE_NONE,
    SPACE_ROUGH,
    SPACE_LANDMARK,
    SPACE_NORMAL
  ],
  BOARD_TILE_L_SHAPED_RIVER => [
    SPACE_LANDMARK,
    SPACE_NONE,
    SPACE_LANDMARK,
    SPACE_ROUGH,
    SPACE_ROUGH,
    SPACE_NONE,
    SPACE_LANDMARK,
    SPACE_LANDMARK,
    SPACE_NONE,
  ],
  BOARD_TILE_DIAGONAL_MEADOW => [
    SPACE_LANDMARK,
    SPACE_NONE,
    SPACE_NONE,
    SPACE_NORMAL,
    SPACE_NONE,
    SPACE_NORMAL,
    SPACE_NONE,
    SPACE_LANDMARK,
    SPACE_NORMAL,
  ],
  BOARD_TILE_CORNER_WATERFALL => [
    SPACE_ROUGH,
    SPACE_LANDMARK,
    SPACE_LANDMARK,
    SPACE_NORMAL,
    SPACE_NORMAL,
    SPACE_NORMAL,
    SPACE_NONE,
    SPACE_NONE,
    SPACE_LANDMARK,
  ],
  BOARD_TILE_V_ROCKS => [
    SPACE_NORMAL,
    SPACE_ROUGH,
    SPACE_LANDMARK,
    SPACE_NONE,
    SPACE_NONE,
    SPACE_NORMAL,
    SPACE_LANDMARK,
    SPACE_ROUGH,
    SPACE_LANDMARK,
  ],
  BOARD_TILE_SINGLE_SNOW => [
    SPACE_LANDMARK,
    SPACE_NORMAL,
    SPACE_NONE,
    SPACE_NONE,
    SPACE_NONE,
    SPACE_NORMAL,
    SPACE_NONE,
    SPACE_NONE,
    SPACE_NONE,
  ]
];