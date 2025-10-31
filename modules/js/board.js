define(['dojo', 'dojo/_base/declare', 'ebg/counter'], (dojo, declare) => {
  const DIRECTIONS = [
    [-1, 0],
    [1, 0],
    [0, 1],
    [0, -1],
    [0, 0],
  ];

  const DESTINATION = 'destination';
  const START = 'start';

  // Everything ralted to playerboards
  return declare('tembo.board', null, {
    constructor() {
      this._notifications.push('boardTileRotated');
    },

    setupBoard() {
      let board = this.gamedatas.board.tiles;
      board.destination.id = DESTINATION;
      board.start.id = START;

      let maxX = 0,
        maxY = 0;
      let tiles = [board.destination, board.start, ...board.tiles];
      let squares = [];
      tiles
        .sort((tile1, tile2) => (tile1.x == tile2.x ? tile1.y - tile2.y : tile1.x - tile2.x))
        .forEach((tile) => {
          let w = 6,
            h = 6;
          let className = 'tile';
          if ([DESTINATION, START].includes(tile.id)) {
            className = tile.id;
            w = tile.rotation % 2 == 0 ? 6 : 3;
            h = tile.rotation % 2 == 0 ? 3 : 6;

            if (tile.flipped) {
              className += ' flipped';
            }
          }

          let additionalContent = '';
          if (tile.id == START) {
            for (let i = 0; i <= 12; i++) {
              additionalContent += `<div class='energy-slot' id='energy-${i}'></div>`;
            }
          }

          maxX = Math.max(maxX, tile.x + w);
          maxY = Math.max(maxY, tile.y + h);
          $('tembo-board').insertAdjacentHTML(
            'beforeend',
            `<div class='board-${className}' data-id='${tile.id}' style="grid-column-start:${tile.x + 1}; grid-row-start:${tile.y + 1}" data-rotation="${tile.rotation}">${additionalContent}</div>`
          );

          // No cell for starting tile
          if (tile.id == START) {
            return;
          }

          for (let i = 0; 3 * i < w; i++) {
            for (let j = 0; 3 * j < h; j++) {
              squares.push({ x: tile.x + 3 * i, y: tile.y + 3 * j });
            }
          }
        });

      $('tembo-board-resizable').dataset.x = maxX;
      $('tembo-board-resizable').dataset.y = maxY;

      squares.forEach((square) => {
        $('tembo-board').insertAdjacentHTML(
          'beforeend',
          `<div class='board-square' id='square-${square.x}-${square.y}' style="grid-column-start:${square.x + 1}; grid-row-start:${square.y + 1}"></div>`
        );

        for (let i = 0; i < 3; i++) {
          for (let j = 0; j < 3; j++) {
            $('tembo-board').insertAdjacentHTML(
              'beforeend',
              `<div class='board-cell' id="cell-${square.x + i}-${square.y + j}" style="grid-column-start:${square.x + i + 1}; grid-row-start:${square.y + j + 1}"></div>`
            );
          }
        }
      });

      // DEBUGGING : BLOCKS
      const MAP = {
        0: 'SNOW',
        1: 'MEADOW',
        2: 'RIVER',
        3: 'ROCKS',
        4: 'CANYON',
        5: 'WATERFALL',

        10: 'ALL GAIN 2',
        11: 'ANOTHER GAINS 4',
        12: 'YOU 5 ANOTHER -2',
        13: 'GAIN 3 IGNORE ROUGH',
      };

      this.gamedatas.board.squares.forEach((square) => {
        $('tembo-board').insertAdjacentHTML(
          'beforeend',
          `<div class='board-square board-square' style="grid-column-start:${square.x + 1}; grid-row-start:${square.y + 1}">${MAP[square.type]}</div>`
        );
      });

      // DEBUGGING : CELLS
      Object.entries(this.gamedatas.board.cells).forEach(([x, column]) => {
        Object.entries(column).forEach(([y, type]) => {
          $(`cell-${x}-${y}`).dataset.type = type;
        });
      });
    },

    getCell(cell) {
      return $(`cell-${cell.x}-${cell.y}`);
    },

    // extractCellFromUId(uid) {
    //   let t = uid.split('-');
    //   return { x: parseInt(t[0]), y: parseInt(t[1]) };
    // },

    notif_boardTileRotated(n) {
      debug('Notif: board tile rotated', n);
      dojo.query('.board-tile').forEach((tile) => {
        if (n.id === parseInt(dojo.attr(tile, 'data-id'))) {
          dojo.attr(tile, 'data-rotation', n.rotation);
        }
      });
    },
  });
});
