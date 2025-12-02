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
  g_gamethemeurl + 'modules/js/States/UseCard.js',
  g_gamethemeurl + 'modules/js/States/PlayerGainLoseElephants.js',
], function (dojo, declare) {
  return declare(
    'bgagame.tembo',
    [
      customgame.game,
      tembo.board,
      tembo.common,
      tembo.lexemes,
      tembo.turn,
      tembo.useCard,
      tembo.htmltemplates,
      tembo.playerGainLoseElephants,
    ],
    {
      constructor() {
        this._notifications = [
          'mediumMessage',
          'longMessage',
          'clearTurn',
          'refreshUI',
          'refreshHand',
          'cardPlacedOnBoard',
          'elephantsGained',
          'elephantsLost',
          'elephantsPlaced',
          'cardsDrawn',
          'energyChanged',
          'treesEaten',
          'lionsMoved',
          'elephantsEaten',
          'matriarchAction',
          'matriarchCardDiscarded',
          'landmarkVisited',
          'newEvent',
        ];
        // this.default_viewport = 'width=990';
      },

      setup(gamedatas) {
        debug('SETUP', gamedatas);

        this.setupCentralArea();
        this.setupBoard();
        this.setupPlayers();
        this.setupMeeples();
        this.setupCards();
        this.setupInfoPanel();
        this.updateDestinationLockedStatus();

        this.inherited(arguments);
      },

      setupCentralArea() {
        $('game_play_area').insertAdjacentHTML('beforeend', this.centralAreaHtml());
      },

      notif_refreshUI(args) {
        ['meeples', 'players', 'cards', 'energy', 'supportTokens'].forEach((value) => {
          this.gamedatas[value] = args.datas[value];
        });
        this._supportCounter.toValue(this.gamedatas.supportTokens);
        this._energyCounter.toValue(this.gamedatas.energy);

        this.setupMeeples();
        this.setupCards();
        this.refreshPlayers();
      },

      notif_refreshHand(args) {
        this.gamedatas.players[args.player_id].hand = args.hand;
        this.setupHand(args.hand);
      },

      notif_mediumMessage(args) {
        return this.wait(900);
      },

      notif_longMessage(args) {
        return this.wait(1200);
      },

      updateInfosFromNotif(infos) {
        debug('updateInfosFromNotif', infos);

        if (infos.supportTokens !== undefined) {
          this._supportCounter.toValue(infos.supportTokens);
        }

        if (infos.matriarchCount !== undefined) {
          Object.entries(infos.matriarchCount).forEach(([pId, v]) => {
            this._matriarchCounters[pId].toValue(v);
          });
        }

        if (infos.deckRemaining) {
          for (let i = 1; i <= 4; i++) {
            this._deckCounters[i].toValue(infos.deckRemaining[i]);
          }
        }

        if (infos.destinationUnlocked) {
          this.gamedatas.destinationUnlocked = infos.destinationUnlocked;
          this.updateDestinationLockedStatus();
        }
      },

      updateDestinationLockedStatus() {
        $('tembo-board').querySelector('.board-destination').dataset.state = this.gamedatas.destinationUnlocked ? 'unlocked' : '';
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
        this._matriarchCounters = {};

        this.getPlayers().forEach((player, i) => {
          if (player.hand && player.id === this.player_id) {
            this.setupHand(player.hand);
          }

          this.getPlayerPanelElement(player.id).insertAdjacentHTML('beforeend', this.tplPlayerPanel(player));

          // Mutators observers
          ['elephant', 'tired-elephant'].forEach((type) => {
            const reserve = $(`${type}-reserve-${player.id}`);
            let observer = new MutationObserver(() => {
              let n = reserve.childNodes.length;
              let counter = $(`${type}-reserve-${player.id}-counter`);
              counter.innerHTML = n;
              counter.parentNode.dataset.n = n;
            });
            observer.observe(reserve, { childList: true });
          });

          // Matriarch counter
          this._matriarchCounters[player.id] = this.createCounter(`matriarch-cards-${player.id}-counter`, player.matriarchCount);
        });
      },

      refreshPlayers() {
        this.getPlayers().forEach((player, i) => {
          this._matriarchCounters[player.id].toValue(player.matriarchCount);
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
          if (!$(`savanna-card-${card.id}`)) {
            this.addCard(card);
          }

          let o = $(`savanna-card-${card.id}`);
          if (!o) return null;

          let container = this.getCardContainer(card);
          if (o.parentNode != $(container)) {
            dojo.place(o, container);
          }
          o.dataset.state = card.state;
          o.dataset.rotation = card.rotation;

          return card.id;
        });
        document.querySelectorAll('.tembo-savanna-card[id^="savanna-card-"]').forEach((oCard) => {
          if (!cardIds.includes(parseInt(oCard.getAttribute('data-id'))) && oCard.parentNode.id != 'savanna-cards-holder') {
            this.destroy(oCard);
          }
        });
      },

      setupHand(cards) {
        cards.forEach((card) => this.addCard(card));
      },

      addCard(card, location = null) {
        if ($('savanna-card-' + card.id)) return;

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
        let t = card.location.split('-');
        if (t[0] == 'hand') {
          return $('savanna-cards-holder');
        }
        if (t[0] == 'board') {
          return $(`square-${card.x}-${card.y}`);
        }

        console.error('Trying to get container of a card', card);
        return 'game_play_area';
      },

      async notif_cardPlacedOnBoard(args) {
        let card = args.card;
        if (!$(`savanna-card-${card.id}`)) {
          this.addCard(card, this.getVisibleTitleContainer());
        }
        $(`savanna-card-${card.id}`).dataset.rotation = card.rotation;
        await this.slide(`savanna-card-${card.id}`, this.getCardContainer(card));
      },

      async notif_cardsDrawn(args) {
        if (args.player_id !== this.player_id) return;

        await Promise.all(
          args.cards.map((card, i) => {
            this.addCard(card, this.getVisibleTitleContainer());
            return this.slide(`savanna-card-${card.id}`, this.getCardContainer(card), { delay: 100 * i });
          })
        );
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
        let t = ('' + loc).split('-');
        let firstType = meeple.type.split('-')[0];

        if (loc == 'reserve' && firstType == 'landmark') {
          return $('landmarks-reserve');
        }

        if (loc == 'table' && firstType == 'tree') {
          return $('trees-holder');
        }

        if (loc == 'board') {
          return this.getCell(meeple);
        }

        if (t[0] == 'reserve') {
          return $(`${meeple.state == 0 ? 'tired-' : ''}elephant-reserve-${t[1]}`);
        }

        if (firstType == 'energy') {
          return $(`energy-${loc}`);
        }

        console.error('Trying to get container of a meeple', meeple);
        return 'game_play_area';
      },

      async notif_elephantsLost(args) {
        await Promise.all(
          args.lost.map((meeple, i) => this.slide(`meeple-${meeple.id}`, this.getMeepleContainer(meeple), { delay: 100 * i }))
        );
      },

      async notif_elephantsGained(args) {
        await Promise.all(
          args.gained.map((meeple, i) => this.slide(`meeple-${meeple.id}`, this.getMeepleContainer(meeple), { delay: 100 * i }))
        );
      },

      async notif_elephantsPlaced(args) {
        if (args.card) {
          if (!$(`savanna-card-${args.card.id}`)) {
            this.addCard(args.card, this.getPlayerPanelElement(args.player_id));
          }

          await this.slide(`savanna-card-${args.card.id}`, this.getVisibleTitleContainer(), { destroy: true });
        }

        await Promise.all(
          args.elephants.map((meeple, i) =>
            this.slide(`meeple-${meeple.id}`, this.getMeepleContainer(meeple), { delay: 100 * i })
          )
        );
      },

      async notif_energyChanged(args) {
        this._energyCounter.toValue(args.energy);
        await this.slide('meeple-energy', `energy-${args.energy}`);
      },

      async notif_treesEaten(args) {
        $(`meeple-${args.meeple.id}`).dataset.state = args.meeple.state;
        if (args.amount > 0) {
          this._energyCounter.toValue(args.energy);
        }
        await this.wait(800);
      },

      async notif_lionsMoved(args) {
        await Promise.all(args.cardIds.map((cardId) => this.slideAndDestroy(`savanna-card-${cardId}`)));

        await Promise.all(
          args.lions.map((lion) => {
            let oLion = $(`meeple-${lion.id}`);
            let container = this.getMeepleContainer(lion);

            if (container == oLion.parentNode) {
              oLion.dataset.state = lion.state;
              return this.wait(1000);
            } else {
              return this.slide(oLion, container).then(() => (oLion.dataset.state = lion.state));
            }
          })
        );
      },

      async notif_elephantsEaten(args) {
        await Promise.all(
          args.elephantsEaten.map((elephant, i) => this.slideAndDestroy(`meeple-${elephant.id}`, { delay: 100 * i }))
        );
      },

      async notif_matriarchAction(args) {
        let matriarch = args.matriarch;
        await this.slide(`meeple-${matriarch.id}`, this.getMeepleContainer(matriarch));

        await Promise.all(args.cardIds.map((cardId) => this.slideAndDestroy(`savanna-card-${cardId}`)));

        await Promise.all(
          args.elephants.map((elephant, i) =>
            this.slide(`meeple-${elephant.id}`, this.getMeepleContainer(elephant), { delay: 100 * i })
          )
        );

        await Promise.all(
          args.trees.map((tree) => {
            let oTree = $(`meeple-${tree.id}`);
            if (oTree.dataset.state != tree.state) {
              oTree.dataset.state = tree.state;
              return this.wait(1000);
            } else {
              return true;
            }
          })
        );
      },

      async notif_landmarkVisited(args) {
        this.slide(`meeple-${args.meeple.id}`, this.getMeepleContainer(args.meeple));
        await this.wait(800);
      },

      async notif_matriarchCardDiscarded(args) {
        await this.slideAndDestroy(`savanna-card-${args.cardId}`);
      },

      /////////////////////////////////////
      //  _____                 _
      // | ____|_   _____ _ __ | |_ ___
      // |  _| \ \ / / _ \ '_ \| __/ __|
      // | |___ \ V /  __/ | | | |_\__ \
      // |_____| \_/ \___|_| |_|\__|___/
      /////////////////////////////////////
      setupEvents() {
        $('info-panel').insertAdjacentHTML(
          'beforeend',
          `<div class="info-panel-row" id="events-holder">
            <div id='events-deck-counter'></div>
            <div class='event-slot' id='event-slot-0'></div>
            <div class='event-slot' id='event-slot-1'></div>
          </div>`
        );

        this._eventsDeckCounter = this.createCounter('events-deck-counter', this.gamedatas.events.deckCount);

        this.gamedatas.events.active.forEach((event) => this.addEvent(event));
      },

      addEvent(event) {
        $(`event-slot-${event.state}`).insertAdjacentHTML('beforeend', this.tplEvent(event));

        const descs = [
          _('Gain +2 energy for the blue Tree instead of the standard +1.'),
          _('Gain +2 energy for the brown Tree instead of the standard +1.'),
          _('Lay the blue tree on its side.'),
          _('Lay the green tree on its side.'),

          _('Lay the brown tree on its side.'),
          _('When you Place Elephants on a water space, gain +5 rested Elephants instead of the standard +3.'),
          _('All players lose -2 rested Elephants.'),
          _('When you Place Elephants on a rough terrain space, place 1 rested Elephant instead of the standard 2.'),

          _('When you Place Elephants on a water space, gain +0 rested Elephants instead of the standard +3.'),
          _('When you Place Elephants: cards with this symbol no longer ignore rough terrain.'),
          _('Remove 1 Support token from the game.'),
          _('Lioness activates.'),

          _('Lion activates.'),
          _('Lay the red tree on its side.'),
          _(
            'All players remove 1 rested Elephant from the game (if no rested Elephants available, remove a tired Elephant instead).'
          ),
          _('Lay both Lions down on their side.'),

          _('When you Place Elephants on a rough terrain space, place 3 rested Elephant instead of the standard 2.'),
          _('Gain +1 Energy.'),
          _('Gain +2 energy for the red Tree instead of the standard +1.'),
          _('When you Place Elephants or Build Savanna: cards with this symbol may not be rotated.'),
        ];
        const desc = descs[event.id];
        this.addCustomTooltip(
          `event-${event.id}`,
          `<div class='event-tooltip'>${this.tplEvent(event, 'tooltip-')}
            <div class="event-desc">${desc}</div>
          </div>`
        );
      },

      async notif_newEvent(args) {
        let oldEvent = $('event-slot-1').querySelector('.tembo-event');
        if (oldEvent) {
          await this.slideAndDestroy(oldEvent);
        }

        let prevEvent = $('event-slot-0').querySelector('.tembo-event');
        if (prevEvent) {
          await this.slide(prevEvent, `event-slot-1`);
        }

        this.addEvent(args.event);
        await this.slide(`event-${args.event.id}`, 'event-slot-0', { from: 'events-deck-counter' });
        this._eventsDeckCounter.incValue(-1);
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

        this._energyCounter = this.createCounter('energy-counter', this.gamedatas.energy);
        this._supportCounter = this.createCounter('support-counter', this.gamedatas.supportTokens);

        this._deckCounters = {};
        for (let i = 1; i <= 4; i++) {
          this._deckCounters[i] = this.createCounter(`deck-info-${i}`, this.gamedatas.deckRemaining[i], { dataset: true });
        }

        if (this.gamedatas.events.deckCount) {
          this.setupEvents();
        }

        this.addCustomTooltip('deck-info-4', _('Support cards left in the deck (first part of the draw deck)'));
        this.addCustomTooltip('deck-info-1', _('Cards left in the I deck (second part of the draw deck)'));
        this.addCustomTooltip('deck-info-2', _('Cards left in the II deck (third part of the draw deck)'));
        this.addCustomTooltip('deck-info-3', _('Cards left in the III deck (fourth and final part of the draw deck)'));
      },

      onEnteringStateGameEnd(args) {
        debug('onEnteringStateGameEnd', args);
        if (this.gamedatas.endGameText) {
          dojo.place(this.tplEndGameText(this.gamedatas.endGameStars, this.gamedatas.endGameText), 'popin_showScores');
        }
        this._scoresModal.show();
      },

      onScreenWidthChange() {
        this.updateLayout();
      },

      updateLayout() {
        if (!$('tembo-board')) return;
        $('info-panel').style.zoom = 1;
        $('savanna-cards-container-resizable').style.zoom = 1;

        const ROOT = document.documentElement;

        const WIDTH = $('tembo-main-container').getBoundingClientRect()['width'] - 5;
        const LEFT_COLUMN = 500;
        const RIGHT_COLUMN = $('tembo-board').offsetWidth;

        // VERTICAL
        if (WIDTH < 1053) {
          let scale = (WIDTH - 10) / RIGHT_COLUMN;
          ROOT.style.setProperty('--temboBoardScale', scale);
        }
        // HORIZONTAL
        else {
          let leftZoom = 1;
          let scale = (WIDTH - LEFT_COLUMN - 10) / RIGHT_COLUMN;
          if (scale < 0.9) {
            let leftWidth = (3 / 10) * WIDTH;
            scale = (WIDTH - leftWidth - 10) / RIGHT_COLUMN;
            leftZoom = leftWidth / LEFT_COLUMN;
          }
          if (scale > 1.2) scale = 1.2;
          ROOT.style.setProperty('--temboBoardScale', scale);
          $('info-panel').style.zoom = leftZoom;
          $('savanna-cards-container-resizable').style.zoom = leftZoom;
        }
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

            if (args.eventId !== undefined) {
              args.eventId = this.tplEvent({ id: args.eventId }, 'log-');
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
