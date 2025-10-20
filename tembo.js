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
  g_gamethemeurl + 'modules/js/playerboard.js',
  g_gamethemeurl + 'modules/js/common.js',
  g_gamethemeurl + 'modules/js/lexemes.js',
  g_gamethemeurl + 'modules/js/tpls.js',
  g_gamethemeurl + 'modules/js/States/Turn.js',
], function (dojo, declare) {
  const ANIMAL_CASSOWARY = 'animal-cassowary';
  const ANIMAL_HORNBILL = 'animal-hornbill';
  const ANIMAL_ORANGUTAN = 'animal-orangutan';
  const ANIMAL_RHINOCEROS = 'animal-rhinoceros';
  const ANIMAL_TIGER = 'animal-tiger';
  const ANIMALS = [ANIMAL_CASSOWARY, ANIMAL_HORNBILL, ANIMAL_ORANGUTAN, ANIMAL_RHINOCEROS, ANIMAL_TIGER];

  return declare(
    'bgagame.tembo',
    [customgame.game, tembo.playerboard, tembo.common, tembo.lexemes, tembo.turn, tembo.htmltemplates],
    {
      constructor() {
        // this.default_viewport = 'width=990';
      },

      setup(gamedatas) {
        debug('SETUP', gamedatas);
        this.setupCentralArea();

        this.setupInfoPanel();
        this.setupPlayers();
        this.setupMeeples();
        this.setupFlowerCards();
        this.updateZonesStatuses();

        // Ecosystems
        if (gamedatas.ecosystemsTexts) {
          this.setupEcosystemCards();
        }

        this.inherited(arguments);
      },

      setupCentralArea() {
        $('game_play_area').insertAdjacentHTML('beforeend', this.centralAreaHtml());
      },

      /////////////////////////////////
      //      ____              _
      //     / ___|__ _ _ __ __| |___
      //    | |   / _` | '__/ _` / __|
      //    | |__| (_| | | | (_| \__ \
      //     \____\__,_|_|  \__,_|___/
      /////////////////////////////////

      setupFlowerCards() {
        this.gamedatas.flowerCards.forEach((card) => {
          if (!$(`flower-card-${card.id}`)) {
            this.addFlowerCard(card);
          }

          let o = $(`flower-card-${card.id}`);
          if (!o) return null;
        });
      },

      addFlowerCard(card, container = null) {
        let o = this.place('tplFlowerCard', card, container || $('flower-cards-holder'));

        let names = {
          b: _('Blue'),
          y: _('Yellow'),
          r: _('Red'),
          w: _('White'),
          g: _('Grey'),
          j: _('Multicolor'),
        };
        let colors = card.flowers.map((type) => names[type]);
        let text = colors.join(', ');

        let tooltip = `<div class='card-tooltip'>
          ${this.tplFlowerCard(card, true)}
          <div class='tooltip-text'>
            <h2>${_('Flower card')}</h2>
            <p>
              ${_('Color(s):') + text}
            </p>
          </div>
        </div>`;

        this.addCustomTooltip(o.id, tooltip, { midSize: false });
      },

      setupEcosystemCards() {
        $('ebd-body').dataset.ecosystems = '1';
        Object.entries(this.gamedatas.ecosystemsTexts).forEach(([id, text]) => {
          let o = this.place('tplEcosystemCard', { id }, $('ecosystem-cards-holder'));

          let tooltip = `<div class='card-tooltip'>
            ${this.tplEcosystemCard({ id }, true)}
            <div class='tooltip-text'>
              <h2>${this.format_string_recursive(_('Ecosystem card n°${id}'), { id })}</h2>
              <p>
                ${_(text)}
              </p>
            </div>
          </div>`;
          this.addCustomTooltip(o.id, tooltip, { midSize: false });
          $(o.id).addEventListener('click', (evt) => this.tooltips[o.id].hoverCallback(evt));
        });
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
        // Pangolin
        if (meeple.type == 'pangolin') {
          if (meeple.location == 'table') return $('flower-cards-holder');
          else return $(`pangolin-${meeple.location}`);
        }
        // Reserve
        if (meeple.location == 'reserve') {
          return $(`animal-reserve-${meeple.type}`);
        }
        // Board
        if (meeple.location == 'table') {
          return $(`cell-${meeple.pId}-${meeple.x}-${meeple.y}`);
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
        dojo.place(this.tplInfoPanel(ANIMALS), 'flower-cards-container', 'first');

        // Mutators observers
        ANIMALS.forEach((type) => {
          const reserve = $(`animal-reserve-${type}`);
          let observer = new MutationObserver(() => {
            let n = reserve.childNodes.length;
            let counter = $(`animal-reserve-${type}-counter`);
            counter.innerHTML = n;
            counter.parentNode.dataset.n = n;
          });
          observer.observe(reserve, { childList: true });
        });

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

        const COLORS_FULL_TYPE = {
          b: 'flower-blue',
          y: 'flower-yellow',
          r: 'flower-red',
          w: 'flower-white',
          g: 'flower-grey',
          j: 'flower-joker',
        };
        if (COLORS_FULL_TYPE[type] !== undefined) type = COLORS_FULL_TYPE[type];

        let text = n == null ? '' : `<span>${n}</span>`;
        return `${text}<div class="icon-container icon-container-${type}">
            <div class="tembo-icon icon-${type}"></div>
          </div>`;
      },

      formatString(str) {
        const ICONS = ['ANIMAL-CASSOWARY', 'ANIMAL-ORANGUTAN', 'ANIMAL-TIGER', 'ANIMAL-HORNBILL', 'ANIMAL-RHINOCEROS', 'TREE-5'];

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
    }
  );
});
