define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const LOCATION_TABLE = 'table';

  return declare('tembo.common', null, {
    constructor() {
      this._notifications.push('pangolinMovedToMarket');
      this._notifications.push('newScores');
      this._notifications.push('endGameScores');
    },

    extractId(element, prefix) {
      const unparsed = element.getAttribute('id').replace(`${prefix}-`, '');
      return isNaN(parseInt(unparsed)) ? unparsed : parseInt(unparsed);
    },

    notif_pangolinMovedToMarket(args) {
      debug('Notif: pangolinMovedToMarket', args);
      this.gamedatas.pangolin = LOCATION_TABLE;
      return this.slide('meeple-pangolin', $('flower-cards-holder'));
    },

    notif_endGameScores(args) {
      debug('Notif: endGameScores', args);
      this.gamedatas.endGameStars = args.stars;
      this.gamedatas.endGameText = args.text;
    },
  });
});
