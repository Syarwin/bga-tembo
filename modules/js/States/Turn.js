define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('tembo.turn', null, {
    constructor() {},

    onEnteringStateSittingAroundTable() {
      if (this.isSpectator) return;

      ['up', 'right', 'down', 'left'].forEach((rotation, i) => {
        this.addPrimaryActionButton(`btn${i}`, `<i class="fa fa-long-arrow-${rotation}"></i>`, () => {
          if ($(`btn${i}`).classList.contains('selected')) return;

          this.bgaPerformAction('actSittingAroundTable', { rotation: i }, { checkAction: false });
        });
      });

      if (this.isCurrentPlayerActive()) {
      } else {
        $(`btn${this.gamedatas.players[this.player_id].rotation}`).classList.add('selected');
        this.addIChangedMindButton();
      }
    },

    notif_updateSittingAroundTable(args) {
      for (let i = 0; i < 4; i++) $(`btn${i}`).classList.remove('selected');
      this.gamedatas.players[this.player_id].rotation = args.rotation;
      if (args.rotation == -1) {
        if ($('btnChangedMind')) $('btnChangedMind').remove();
      } else {
        $(`btn${args.rotation}`).classList.add('selected');
        this.addIChangedMindButton();
      }
    },

    addIChangedMindButton() {
      this.addPrimaryActionButton('btnChangedMind', _('I changed my mind'), () => {
        this.bgaPerformAction('actChangedMind', {}, { checkAction: false });
      });
    },

    onEnteringStateTurnBoardTile() {
      if (!this.isCurrentPlayerActive()) return;

      this.addPrimaryActionButton('btnOk', _('Do nothing'), () => {
        this.bgaPerformAction('actLeaveBoardTiles');
      });
      // this.addPrimaryActionButton('btnReorient', _('Reorient id 1 to 2'), () => {
      //   this.bgaPerformAction('actReorientBoardTile', { id: 1, rotation: 2 });
      // });

      let selectedTile = null;
      let initialRotation = null;

      let incRotation = (oTile, c) => {
        if (selectedTile !== oTile) {
          if (selectedTile !== null) {
            selectedTile.classList.remove('selected');
            selectedTile.dataset.rotation = initialRotation;
            selectedTile.style.rotate = null;
          }

          selectedTile = oTile;
          initialRotation = oTile.dataset.rotation;
          oTile.classList.add('selected');
        }

        oTile.dataset.rotation = +oTile.dataset.rotation + c;
        oTile.style.rotate = +oTile.dataset.rotation * 90 + 'deg';

        $('btnOk').classList.add('disabled');
        this.addPrimaryActionButton('btnConfirm', _('Confirm'), () =>
          this.bgaPerformAction('actReorientBoardTile', {
            id: selectedTile.dataset.id,
            rotation: ((selectedTile.dataset.rotation % 4) + 4) % 4,
          })
        );
        this.addSecondaryActionButton('btnCancel', _('Cancel'), () => {
          selectedTile.classList.remove('selected');
          selectedTile.dataset.rotation = initialRotation;
          selectedTile.style.rotate = null;
          selectedTile = null;
          $('btnOk').classList.remove('disabled');
          $('btnCancel').remove();
        });
      };

      $('tembo-board')
        .querySelectorAll('.board-tile')
        .forEach((oTile) => {
          oTile.classList.add('selectable');
          let id = oTile.dataset.id;
          oTile.insertAdjacentHTML(
            'beforeend',
            `<div class='rotate-tile' id="rotate-clockwise-${id}"><i class="fa fa-repeat"></i></div>`
          );

          this.onClick(`rotate-clockwise-${id}`, () => incRotation(oTile, 1));
        });
    },

    onLeavingStateTurnBoardTile() {
      $('tembo-board')
        .querySelectorAll('.board-tile .rotate-tile')
        .forEach((o) => o.remove());
    },

    onEnteringStateDiscardSecondMatriarch() {
      if (this.isCurrentPlayerActive()) {
        this.addPrimaryActionButton('btnDiscard', _('Discard'), () => {
          this.takeAtomicAction('actDiscardSecondMatriarch');
        });
        this.addPrimaryActionButton('btnNothing', _('Play Matriarch'), () => {
          this.takeAtomicAction('actDoNotDiscardSecondMatriarch');
        });
      }
    },
  });
});
