define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('tembo.useCard', null, {
    constructor() {},

    onEnteringStateUseCard(args) {
      args.cardIds.forEach((cardId) => {
        this.onClick(`savanna-card-${cardId}`, () => {
          args.cardId = cardId;
          if (args.patterns[cardId] !== undefined) {
            this.clientState('useCardChooseOption', _('How do you want to use that card?'), args);
          } else {
            this.moveToPlaceCardState(args);
          }
        });
      });
    },

    onEnteringStatePlaceCard(args) {
      $(`savanna-card-${args.cardId}`).classList.add('selected');
      this.addCancelStateBtn();

      args.squares.forEach((square) => {
        this.onClick(`square-${square.x}-${square.y}`, () => {
          this.takeAtomicAction('actPlaceCard', [args.cardId, square.x, square.y]);
        });
      });
    },

    onEnteringStateUseCardChooseOption(args) {
      $(`savanna-card-${args.cardId}`).classList.add('selected');
      this.addPrimaryActionButton('btnPlaceCard' , 'Build the savanna', () => {
        this.moveToPlaceCardState(args);
      });
      this.addPrimaryActionButton('btnPlaceElephants' , 'Place elephants', () => {
        this.clientState('placeElephants', _('Select where to place elephants on the board'), args);
      });
      this.addCancelStateBtn();
    },

    moveToPlaceCardState(args) {
      this.clientState('placeCard', _('Where do you want to place that card?'), args);
    },

    onEnteringStatePlaceElephants(args) {
      // TODO: Delete everything and change to clicking on the board
      $(`savanna-card-${args.cardId}`).classList.add('selected');
      this.addPrimaryActionButton('btnPlaceElephants' , 'Use first available option', () => {
        this.takeAtomicAction('actPlaceElephants', { cardId: args.cardId, patternIndex: 0 });
      });
      this.addCancelStateBtn();
    },
  });
});
