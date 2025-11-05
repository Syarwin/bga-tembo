define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('tembo.playerGainLoseElephants', null, {
    constructor() {},

    onEnteringStatePlayerGainLoseElephants(args) {
      if (this.isCurrentPlayerActive()) {
        args.players.forEach((player) => {
          this.addPrimaryActionButton(`btnPlayer${player.id}` , player.name, () => {
            this.takeAtomicAction('actChoosePlayerGainLoseElephants', { pId: player.id });
          });
        });
      }
    },
  });
});
