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
            this.clientState('placeCard', _('Where do you want to place that card?'), args);
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
  });
});
