define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('tembo.useCard', null, {
    constructor() {},

    onEnteringStateUseCard(args) {
      args.cardIds.forEach((cardId) => {
        this.onClick(`savanna-card-${cardId}`, () => {
          args.cardId = cardId;
          if (args.patterns[cardId] !== undefined || args.singleSpaces.length > 0) {
            this.clientState('useCardChooseOption', _('How do you want to use that card?'), args);
          } else {
            this.moveToPlaceCardState(args);
          }
        });
      });

      if (args.matriarchIds) {
        let callback = () => this.clientState('playMatriarch', _('Where do you want to move the Matriarch?'), args);
        this.addPrimaryActionButton('btnMatriarch', _('Play a Matriarch card'), callback);
        args.matriarchIds.forEach((cardId) => this.onClick(`savanna-card-${cardId}`, callback));
      }

      if (args.supportTokens > 0) {
        this.addPrimaryActionButton('btnTest', _('Use a support token'), () =>
          this.clientState('useSupportToken', _('How do you want to use the support token?'), {})
        );
      }
    },

    onEnteringStateUseSupportToken(args) {
      let btns = {
        0: _('Gain +1 Energy'),
        1: _('Gain +2 rested Elephants'),
        2: _('Rotate next card'),
        3: _('Place 1 rested Elephant (ignore rough)'),
      };

      Object.entries(btns).forEach(([option, txt]) => {
        this.addPrimaryActionButton(`btnToken${option}`, txt, () => this.takeAtomicAction('actUseSupportToken', [option]));
      });
    },

    onEnteringStateUseCardChooseOption(args) {
      $(`savanna-card-${args.cardId}`).classList.add('selected');
      this.addPrimaryActionButton('btnPlaceCard', _('Build the savanna'), () => {
        this.moveToPlaceCardState(args);
      });
      if (args.patterns[args.cardId] !== undefined) {
        this.addPrimaryActionButton('btnPlaceElephants', _('Place elephants'), () => {
          this.clientState('placeElephants', _('Select where to place elephants on the board'), args);
        });
      }
      const spaces = args.ignoreRoughCardIds.includes(args.cardId) ? args.singleSpacesIgnoreRough : args.singleSpaces;
      if (spaces.length > 0) {
        this.addPrimaryActionButton('btnPlaceSingleElephant', _('Place a single elephant'), () => {
          this.clientState('placeSingleElephant', _('Select where to place an elephant on the board'), args);
        });
      }
      this.addCancelStateBtn();
    },

    moveToPlaceCardState(args) {
      this.clientState('placeCard', _('Where do you want to place that card?'), args);
    },

    onEnteringStatePlaceCard(args) {
      $(`savanna-card-${args.cardId}`).classList.add('selected');
      this.addCancelStateBtn();

      args.squares.forEach((square) => {
        this.onClick(`square-${square.x}-${square.y}`, () => {
          // Can rotate the card ?
          if (args.rotatableCardIds.includes(args.cardId)) {
            args.square = square;
            this.clientState('chooseCardRotation', _('How do you want to rotate that card?'), args);
          }
          // No => standard rotation of current player
          else {
            this.takeAtomicAction('actPlaceCard', [args.cardId, square.x, square.y, args.rotation]);
          }
        });
      });
    },

    onEnteringStateChooseCardRotation(args) {
      let oCard = $(`savanna-card-${args.cardId}`);
      oCard.classList.add('selected');
      this.addCancelStateBtn();

      let tmpCard = oCard.cloneNode(true);
      tmpCard.id = 'tmpCard';
      tmpCard.classList.add('tmp');
      tmpCard.dataset.rotation = args.rotation;
      $(`square-${args.square.x}-${args.square.y}`).insertAdjacentElement('beforeend', tmpCard);
      // tmpCard.insertAdjacentHTML(
      //   'beforeend',
      //   `
      //   <div id="card-rotate-clockwise"><svg><use href="#rotate-clockwise-svg" /></svg></div>
      //   <div id="card-rotate-cclockwise"><svg><use href="#rotate-cclockwise-svg" /></svg></div>
      //   <div id="card-confirm-btn" class="action-button bgabutton bgabutton_blue">✓</div>
      // `
      // );

      let incRotation = (delta) => {
        tmpCard.dataset.rotation = (+tmpCard.dataset.rotation + delta + 4) % 4;
      };
      this.addPrimaryActionButton('btnRotateCClockwise', '<i class="fa fa-undo"></i>', () => incRotation(-1));
      this.addPrimaryActionButton('btnRotateClockwise', '<i class="fa fa-repeat"></i>', () => incRotation(1));
      this.addPrimaryActionButton('btnConfirm', _('Confirm'), () =>
        this.takeAtomicAction('actPlaceCard', [args.cardId, args.square.x, args.square.y, +tmpCard.dataset.rotation])
      );
    },

    onEnteringStatePlaceSingleElephant(args, isMatriarch = false) {
      let spaces = args.singleSpaces;
      if (isMatriarch) {
        spaces = args.singleSpacesMatriarch;
      } else if (args.ignoreRoughCardIds === undefined || args.ignoreRoughCardIds.includes(args.cardId)) {
        spaces = args.singleSpacesIgnoreRough;
      }
      spaces.forEach((cell) => {
        this.onClick(`cell-${cell.x}-${cell.y}`, () => {
          if (isMatriarch) {
            this.takeAtomicAction('actPlayMatriarch', { x: cell.x, y: cell.y });
          } else {
            this.takeAtomicAction('actPlaceSingleElephant', { x: cell.x, y: cell.y, cardId: args.cardId });
          }
        });
      });
      if (!isMatriarch) {
        this.addCancelStateBtn();
      }
    },

    onEnteringStatePlayMatriarch(args) {
      this.addCancelStateBtn();

      if (args.matriarchIds) {
        args.matriarchIds.forEach((cardId) => $(`savanna-card-${cardId}`).classList.add('selected'));
      }

      this.addPrimaryActionButton('btnNotMovingMatriarch', 'Do not move the Matriarch', () => {
        this.takeAtomicAction('actLeaveMatriarch');
      });
      this.onEnteringStatePlaceSingleElephant(args, true);
    },

    onLeavingStatePlaceElephants() {
      this._onClickCell = null;
      this._onHoverCell = null;

      [...$(`tembo-board`).querySelectorAll('.board-cell')].forEach((elt) => {
        delete elt.style.removeProperty('cursor');
      });

      this.destroy('pattern-controls');
      this.destroy('pattern-hover');
    },

    onEnteringStatePlaceElephants(args) {
      $(`savanna-card-${args.cardId}`).classList.add('selected');
      this.addCancelStateBtn();
      const shape = args.patternsShapes[args.cardId];
      const patterns = args.patterns[args.cardId];
      const canBeRotated = args.rotatableCardIds.includes(args.cardId);

      // Place a pattern at the correct grid position to make at pos (x,y)
      let placePattern = (patternId, x, y) => {
        let col = parseInt(x) + 1;
        let row = parseInt(y) + 1;
        $(patternId).style.gridColumnStart = col;
        $(patternId).style.gridRowStart = row;
      };

      let rotation = args.rotation;
      let hoveredCell = null;
      let pos = null;
      let oBoard = $('tembo-board');

      // Add a visual representation on hover
      oBoard.insertAdjacentHTML(
        'beforeend',
        `<div id='pattern-controls' class='inactive'>
        <div id='pattern-controls-circle'>
          ${
            canBeRotated
              ? `<div id="pattern-rotate-clockwise"><svg><use href="#rotate-clockwise-svg" /></svg></div>
                 <div id="pattern-rotate-cclockwise"><svg><use href="#rotate-cclockwise-svg" /></svg></div>`
              : ''
          }
          <div id="pattern-move-up"><i class="fa fa-long-arrow-up"></i></div>
          <div id="pattern-move-right"><i class="fa fa-long-arrow-right"></i></div>
          <div id="pattern-move-down"><i class="fa fa-long-arrow-down"></i></div>
          <div id="pattern-move-left"><i class="fa fa-long-arrow-left"></i></div>
          <div id="pattern-confirm-btn" class="action-button bgabutton bgabutton_blue">✓</div>
        </div>
      </div>`
      );
      oBoard.insertAdjacentHTML('beforeend', this.tplPattern(shape, 'pattern-hover'));
      placePattern('pattern-hover', 2, 2);
      placePattern('pattern-controls', 2, 2);
      $('pattern-hover').style.transform = `rotate(${rotation * 90}deg)`;

      // Add pattern selectors in pagetitle
      $('pagesubtitle').insertAdjacentHTML('beforeend', '<div id="pattern-selector"></div>');
      $('pattern-selector').insertAdjacentHTML('beforeend', this.tplPattern(shape, '', args.rotation));

      // Compute new size of circle control
      let w = $('pattern-hover').offsetWidth;
      let h = $('pattern-hover').offsetHeight;
      let cross = $('pattern-hover').querySelector('.pattern-crosshairs');
      let offsetW = cross.offsetLeft + cross.offsetWidth / 2;
      let offsetH = cross.offsetTop + cross.offsetHeight / 2;
      let dx = Math.max(offsetW, w - offsetW);
      let dy = Math.max(offsetH, h - offsetH);
      let radius = Math.sqrt(dx * dx + dy * dy) + 10;
      $('pattern-controls-circle').style.width = 2 * radius + 'px';
      $('pattern-controls-circle').style.height = 2 * radius + 'px';

      // Move selection to a given position
      let moveSelection = (x, y, cell = null) => {
        placePattern('pattern-hover', x, y);
        placePattern('pattern-controls', x, y);

        let r = ((rotation % 4) + 4) % 4;
        let pos = patterns.find((p) => p.pos.x == x && p.pos.y == y && p.r == r);
        let valid = pos !== undefined;
        $('pattern-hover').classList.toggle('invalid', !valid);
        $('pattern-hover').style.transform = `rotate(${rotation * 90}deg)`;
        $('pattern-hover').querySelector('.pattern-crosshairs').style.transform = `rotate(${-rotation * 90}deg)`;

        $('pattern-controls').classList.toggle('invalid', !valid);
        $('pattern-controls').classList.remove('inactive');

        if (cell === null) {
          cell = oBoard.querySelector(`[data-x='${x}'][data-y='${y}']`);
        }
        if (cell) {
          cell.style.cursor = valid ? 'pointer' : 'not-allowed';
        }

        // Update button status
        if ($('btnConfirmBuild')) {
          $('btnConfirmBuild').classList.toggle('disabled', !valid);
          $('pattern-confirm-btn').classList.toggle('disabled', !valid);
        }
      };
      let updateSelection = () => {
        if (hoveredCell) {
          moveSelection(hoveredCell.dataset.x, hoveredCell.dataset.y, hoveredCell);
        } else if (pos.x == 0 && pos.y == 0) {
          moveSelection(0, 0);
        }
      };

      // Listen on hovering on map cells
      this._onHoverCell = (cell) => {
        cell.style.cursor = 'default';
        if (pos == null || (pos.x == 0 && pos.y == 0)) {
          let x = parseInt(cell.dataset.x);
          let y = parseInt(cell.dataset.y);
          hoveredCell = cell;
          moveSelection(x, y, cell);
          $('pattern-hover').classList.add('hovering');
          $('pattern-controls').classList.add('hovering');
        }
      };

      this._onClickCell = (cell) => {
        cell.style.cursor = 'default';
        let x = parseInt(cell.dataset.x);
        let y = parseInt(cell.dataset.y);
        pos = { x, y };
        hoveredCell = cell;
        $('pattern-hover').classList.remove('hovering');
        $('pattern-controls').classList.remove('hovering');

        // Add confirm button
        this.addPrimaryActionButton('btnConfirmBuild', _('Confirm'), () => {
          if (!$('btnConfirmBuild').classList.contains('disabled')) {
            this.takeAtomicAction('actPlaceElephants', [args.cardId, pos, ((rotation % 4) + 4) % 4]);
          }
        });
        moveSelection(x, y, cell);
      };

      if (canBeRotated) {
        // Click on arrow to rotate
        let incRotation = (c) => {
          rotation += c;
          updateSelection();
        };
        this.onClick('pattern-rotate-clockwise', () => incRotation(1));
        this.onClick('pattern-rotate-cclockwise', () => incRotation(-1));
        this.addPrimaryActionButton('btnRotateCClockwise', '<i class="fa fa-undo"></i>', () => incRotation(-1));
        this.addPrimaryActionButton('btnRotateClockwise', '<i class="fa fa-repeat"></i>', () => incRotation(1));
      }

      // Click on arrow to move
      let shiftPattern = (dx, dy) => {
        let x = pos.x + dx,
          y = pos.y + dy;
        hoveredCell = oBoard.querySelector(`[data-x='${x}'][data-y='${y}']`);
        pos = { x, y };
        moveSelection(x, y);
      };
      this.onClick('pattern-move-up', () => shiftPattern(0, -1));
      this.onClick('pattern-move-down', () => shiftPattern(0, 1));
      this.onClick('pattern-move-left', () => shiftPattern(-1, 0));
      this.onClick('pattern-move-right', () => shiftPattern(1, 0));

      // Confirm
      this.onClick('pattern-confirm-btn', () => {
        if (!$('pattern-confirm-btn').classList.contains('disabled')) {
          this.takeAtomicAction('actPlaceElephants', [args.cardId, pos, ((rotation % 4) + 4) % 4]);
        }
      });
    },
  });
});
