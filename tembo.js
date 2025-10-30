/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Tembo implementation : © Timothée (Tisaac) Pecatte <tim.pecatte@gmail.com>, Pavel Kulagin (KuWizard) <kuzwiz@mail.ru>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * tembo.js
 *
 * Tembo user interface script
 *
 */
var isDebug = window.location.host === 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () {};

define([
  'dojo',
  'dojo/_base/declare',
  'ebg/counter',
  g_gamethemeurl + 'modules/js/Core/modal.js',
  g_gamethemeurl + 'modules/js/Core/game.js',
  g_gamethemeurl + 'modules/js/board.js',
  g_gamethemeurl + 'modules/js/common.js',
  g_gamethemeurl + 'modules/js/lexemes.js',
  g_gamethemeurl + 'modules/js/tpls.js',
  g_gamethemeurl + 'modules/js/States/Turn.js',
], function (dojo, declare) {
  return declare('bgagame.tembo', [customgame.game, tembo.board, tembo.common, tembo.lexemes, tembo.turn, tembo.htmltemplates], {
    constructor() {
      // this.default_viewport = 'width=990';
    },

    setup(gamedatas) {
      debug('SETUP', gamedatas);
      this.setupCentralArea();
      this.setupBoard();
      this.setupPlayers();
      this.setupMeeples();

      this.inherited(arguments);
    },

    setupCentralArea() {
      $('game_play_area').insertAdjacentHTML('beforeend', this.centralAreaHtml());
    },

    //////////////////////////////////////////
    //  ____  _
    // |  _ \| | __ _ _   _  ___ _ __ ___
    // | |_) | |/ _` | | | |/ _ \ '__/ __|
    // |  __/| | (_| | |_| |  __/ |  \__ \
    // |_|   |_|\__,_|\__, |\___|_|  |___/
    //                |___/
    //////////////////////////////////////////

    getPlayers() {
      return Object.values(this.gamedatas.players);
    },

    setupPlayers() {
      this.getPlayers().forEach((player, i) => {
        if (player.hand && player.id === this.player_id) {
          this.setupHand(player.hand);
        }
      });
    },

    /////////////////////////////////
    //      ____              _
    //     / ___|__ _ _ __ __| |___
    //    | |   / _` | '__/ _` / __|
    //    | |__| (_| | | | (_| \__ \
    //     \____\__,_|_|  \__,_|___/
    /////////////////////////////////

    // This function is refreshUI compatible
    setupCards() {
      let cardIds = this.gamedatas.cards.map((card) => {
        if (!$(`card-${card.id}`)) {
          this.addCard(card);
        }

        let o = $(`card-${card.id}`);
        if (!o) return null;

        let container = this.getCardContainer(card);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
        }
        o.dataset.state = card.state;

        return card.id;
      });
      document.querySelectorAll('.tembo-card[id^="card-"]').forEach((oCard) => {
        if (!cardIds.includes(parseInt(oCard.getAttribute('data-id')))) {
          this.destroy(oCard);
        }
      });
    },

    setupHand(cards) {
      cards.forEach((card) => this.addCard(card));
    },

    addCard(card, location = null) {
      if ($('card-' + card.id)) return;

      let o = this.place('tplSavannaCard', card, location == null ? this.getCardContainer(card) : location);
      let tooltipDesc = this.getCardTooltip(card);
      if (tooltipDesc != null) {
        this.addCustomTooltip(o.id, tooltipDesc.map((t) => this.formatString(t)).join('<br/>'));
      }

      return o;
    },

    getCardTooltip(card) {
      let type = card.type;
      return null;
    },

    getCardContainer(card) {
      let t = card.location.split('_');
      if (t[0] == 'hand') {
        return $('savanna-cards-holder');
      }

      console.error('Trying to get container of a card', card);
      return 'game_play_area';
    },

    //////////////////////////////////////////
    //  __  __                 _
    // |  \/  | ___  ___ _ __ | | ___  ___
    // | |\/| |/ _ \/ _ \ '_ \| |/ _ \/ __|
    // | |  | |  __/  __/ |_) | |  __/\__ \
    // |_|  |_|\___|\___| .__/|_|\___||___/
    //                  |_|
    //////////////////////////////////////////

    // This function is refreshUI compatible
    setupMeeples() {
      let meepleIds = this.gamedatas.meeples.map((meeple) => {
        if (!$(`meeple-${meeple.id}`)) {
          this.addMeeple(meeple);
        }

        let o = $(`meeple-${meeple.id}`);
        if (!o) return null;

        let container = this.getMeepleContainer(meeple);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
        }
        o.dataset.state = meeple.state;

        return meeple.id;
      });
      document.querySelectorAll('.tembo-meeple[id^="meeple-"]').forEach((oMeeple) => {
        if (!meepleIds.includes(parseInt(oMeeple.getAttribute('data-id')))) {
          this.destroy(oMeeple);
        }
      });

      if (!$('meeple-energy') && this.gamedatas.energy) {
        this.addMeeple({ id: 'energy', location: this.gamedatas.energy, type: 'energy' });
      }
    },

    addMeeple(meeple, location = null) {
      if ($('meeple-' + meeple.id)) return;

      if (meeple.type == 'tree') {
        meeple.type += '-' + 2 * Math.ceil(Math.random() * 4);
      }

      let o = this.place('tplMeeple', meeple, location == null ? this.getMeepleContainer(meeple) : location);
      let tooltipDesc = this.getMeepleTooltip(meeple);
      if (tooltipDesc != null) {
        this.addCustomTooltip(o.id, tooltipDesc.map((t) => this.formatString(t)).join('<br/>'));
      }

      return o;
    },

    getMeepleTooltip(meeple) {
      let type = meeple.type;
      return null;
    },

    getMeepleContainer(meeple) {
      let loc = meeple.location;
      let firstType = meeple.type.split('-')[0];

      if (loc == 'reserve' && firstType == 'landmark') {
        return $('landmarks-reserve');
      }

      if (loc == 'table' && firstType == 'tree') {
        return $('trees-holder');
      }

      console.error('Trying to get container of a meeple', meeple);
      return 'game_play_area';
    },

    //////////////////////////////////////////////////////
    //  ___        __         ____                  _
    // |_ _|_ __  / _| ___   |  _ \ __ _ _ __   ___| |
    //  | || '_ \| |_ / _ \  | |_) / _` | '_ \ / _ \ |
    //  | || | | |  _| (_) | |  __/ (_| | | | |  __/ |
    // |___|_| |_|_|  \___/  |_|   \__,_|_| |_|\___|_|
    //////////////////////////////////////////////////////

    setupInfoPanel() {
      let chk = $('help-mode-chk');
      dojo.connect(chk, 'onchange', () => this.toggleHelpMode(chk.checked));
      this.addTooltip('help-mode-switch', '', _('Toggle help/safe mode.'));
      this.updateTurnNumber();

      // this._settingsModal = new customgame.modal('showSettings', {
      //   class: 'tembo_popin',
      //   closeIcon: 'fa-times',
      //   title: _('Settings'),
      //   closeAction: 'hide',
      //   verticalAlign: 'flex-start',
      //   contentsTpl: `<div id='tembo-settings'>
      //      <div id='tembo-settings-header'></div>
      //      <div id="settings-controls-container"></div>
      //    </div>`,
      // });
    },

    onEnteringStateGameEnd(args) {
      debug('onEnteringStateGameEnd', args);
      if (this.gamedatas.endGameText) {
        dojo.place(this.tplEndGameText(this.gamedatas.endGameStars, this.gamedatas.endGameText), 'popin_showScores');
      }
      this._scoresModal.show();
    },

    // updatePlayerOrdering() {
    //   this.inherited(arguments);
    //   dojo.place('player_board_config', 'player_boards', 'first');
    // },

    updateTurnNumber() {
      $('turn-number').innerHTML = this.gamedatas.turn;
      $('max-turns').innerHTML = this.getPlayers().length == 1 ? 18 : 9;
    },

    ////////////////////////////////////////////////////////////
    // _____                          _   _   _
    // |  ___|__  _ __ _ __ ___   __ _| |_| |_(_)_ __   __ _
    // | |_ / _ \| '__| '_ ` _ \ / _` | __| __| | '_ \ / _` |
    // |  _| (_) | |  | | | | | | (_| | |_| |_| | | | | (_| |
    // |_|  \___/|_|  |_| |_| |_|\__,_|\__|\__|_|_| |_|\__, |
    //                                                 |___/
    ////////////////////////////////////////////////////////////

    /**
     * Replace some expressions by corresponding html formating
     */
    formatIcon(name, n = null, lowerCase = true) {
      let type = lowerCase ? name.toLowerCase() : name;

      let text = n == null ? '' : `<span>${n}</span>`;
      return `${text}<div class="icon-container icon-container-${type}">
            <div class="tembo-icon icon-${type}"></div>
          </div>`;
    },

    formatString(str) {
      const ICONS = [];

      ICONS.forEach((name) => {
        str = str.replaceAll(new RegExp('<' + name + '>', 'g'), this.formatIcon(name));
      });

      return str;
    },

    /**
     * Format log strings
     *  @Override
     */
    format_string_recursive(log, args) {
      try {
        if (log && args && !args.processed) {
          args.processed = true;

          log = this.formatString(_(log));

          if (args.color_icon !== undefined) {
            args.color_icon = this.formatIcon(args.color_type);
            args.color_name = '';
          }
        }
      } catch (e) {
        console.error(log, args, 'Exception thrown', e.stack);
      }

      let str = this.inherited(arguments);
      return this.formatString(str);
    },
  });
});
