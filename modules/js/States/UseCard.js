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
      this.addPrimaryActionButton('btnPlaceCard', 'Build the savanna', () => {
        this.moveToPlaceCardState(args);
      });
      if (args.patterns[args.cardId] !== undefined) {
        this.addPrimaryActionButton('btnPlaceElephants', 'Place elephants', () => {
          this.clientState('placeElephants', _('Select where to place elephants on the board'), args);
        });
      }
      if (args.singleSpaces.length > 0) {
        this.addPrimaryActionButton('btnPlaceSingleElephant', 'Place a single elephant', () => {
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

    onEnteringStatePlaceElephants(args) {
      // TODO: Delete everything and change to clicking on the board
      $(`savanna-card-${args.cardId}`).classList.add('selected');
      this.addPrimaryActionButton('btnPlaceElephants', 'Use first available option', () => {
        this.takeAtomicAction('actPlaceElephants', { cardId: args.cardId, patternIndex: 0 });
      });
      this.addCancelStateBtn();
    },

    onEnteringStatePlaceSingleElephant(args, isMatriarch = false) {
      // TODO: Feel free to refactor this as well
      args.singleSpaces.forEach((cell) => {
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
      this.addPrimaryActionButton('btnNotMovingMatriarch', 'Do not move the Matriarch', () => {
        this.takeAtomicAction('actLeaveMatriarch');
      });
      this.onEnteringStatePlaceSingleElephant(args, true);
    },
  });
});
