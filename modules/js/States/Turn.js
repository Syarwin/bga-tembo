define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('tembo.turn', null, {
    constructor() {},

    onEnteringStateSittingAroundTable() {
      if (this.isCurrentPlayerActive()) {
        this.addIdontCareButton();
      } else {
        this.addIChangedMindButton();
      }
    },

    addIdontCareButton() {
      this.addPrimaryActionButton('btnRandom', _('I don\'t care'), async () => {
        await this.bgaPerformAction('actSittingAroundTable');
        this.statusBar.removeActionButtons(); // TODO: This doesn't work for some reason, buttons remain in the status bar
        this.addIChangedMindButton();
      });
    },

    addIChangedMindButton() {
      this.addPrimaryActionButton('btnChangedMind', _('I changed my mind'), async () => {
        this.bgaPerformAction('actChangedMind', {}, { checkAction: false });
        this.statusBar.removeActionButtons();
        this.addIdontCareButton();
      })
    },
  });
});
