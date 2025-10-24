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
      this._scoresCounters = {};

      // Change No so that it fits the current player order view
      let currentNo = this.getPlayers().reduce((carry, player) => (player.id == this.player_id ? player.no : carry), 0);
      let nPlayers = Object.keys(this.gamedatas.players).length;
      this.forEachPlayer((player) => (player.order = (player.no + nPlayers - currentNo) % nPlayers));
      this.orderedPlayers = Object.values(this.gamedatas.players).sort((a, b) => a.order - b.order);

      // Add player board and player panel
      this.orderedPlayers.forEach((player, i) => {
        // Player board
        this.place('tplPlayerBoard', player, 'player-board-holder');

        // Panels
        this.place('tplPlayerPanel', player, `overall_player_board_${player.id}`);
        $(`overall_player_board_${player.id}`).addEventListener('click', () => this.goToPlayerBoard(player.id));

        // Scores
        this._scoresCounters[player.id] = {};
        ['name', 'trees', 'animals', 'completedAreas', 'unfinishedAndMixed', 'overall'].forEach((scoringCategory) => {
          if (scoringCategory == 'name') {
            $(`score-row-name`).insertAdjacentHTML('beforeend', `<td>${player.name}</td>`);
            return;
          }

          $(`score-row-${scoringCategory}`).insertAdjacentHTML(
            'beforeend',
            `<td><span id='score-${player.id}-${scoringCategory}'></span></td>`
          );

          this._scoresCounters[player.id][scoringCategory] = this.createCounter(
            `score-${player.id}-${scoringCategory}`,
            player.scores[scoringCategory]
          );
        });
      });
    },

    /////////////////////////////////
    //      ____              _
    //     / ___|__ _ _ __ __| |___
    //    | |   / _` | '__/ _` / __|
    //    | |__| (_| | | | (_| \__ \
    //     \____\__,_|_|  \__,_|___/
    /////////////////////////////////

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
      // Init grid for clientside logic
      this._emptyBoard = true;
      this._board = {};
      for (let x = 0; x < 6; x++) {
        this._board[x] = {};
        for (let y = 0; y < 6; y++) {
          this._board[x][y] = [];
        }
      }

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

        // Update board
        if (meeple.location == 'table' && meeple.pId == this.player_id) {
          this._board[meeple.x][meeple.y].push(meeple);
          this._emptyBoard = false;
        }

        return meeple.id;
      });
      document.querySelectorAll('.tembo-meeple[id^="meeple-"]').forEach((oMeeple) => {
        if (!meepleIds.includes(parseInt(oMeeple.getAttribute('data-id')))) {
          this.destroy(oMeeple);
        }
      });

      if (!$('meeple-pangolin') && this.gamedatas.pangolin) {
        this.addMeeple({ id: 'pangolin', location: this.gamedatas.pangolin, type: 'pangolin' });
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

      this._scoresModal = new customgame.modal('showScores', {
        class: 'tembo_popin',
        closeIcon: 'fa-times',
        closeAction: 'hide',
        verticalAlign: 'flex-start',
        contentsTpl: this.tplScoreModal(),
      });
      $('show-scores').addEventListener('click', () => this._scoresModal.show());

      this.addTooltip('score-row-trees', _('Trees: count all your Trees and score 2 points for each.'), '');
      this.addTooltip(
        'score-row-animals',
        _('Animals: each animal scores the point shown on the paw icon in their respective Areas.'),
        ''
      );
      this.addTooltip(
        'score-row-completedAreas',
        _('Each completed Area scores the points shown in their respective Area icon.'),
        ''
      );
      this.addTooltip(
        'score-row-unfinishedAndMixed',
        _(
          'You score minus points for each unfinished and mixed Area. The minus value is the same as shown in their respective Area icon.'
        ),
        ''
      );

      if (this.gamedatas.ecosystemsTexts) {
        Object.entries(this.gamedatas.ecosystemsTexts).forEach(([id, text]) => {
          this.addTooltip(`score-row-ecosystem-${id}`, _(text), '');
        });
      }
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
