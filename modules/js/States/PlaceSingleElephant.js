define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('tembo.placeSingleElephant', null, {
    constructor() {},

    onEnteringStatePlaceSingleElephant(args) {
      if (this.isCurrentPlayerActive()) {
        const first = args.options[0];
        if (first) {
          this.addPrimaryActionButton('btnaaa' , `Place at ${first.x}, ${first.y}`, () => {
            this.takeAtomicAction('actPlaceSingleElephant', { x: first.x, y: first.y });
          });
        }
      }
    },
  });
});
