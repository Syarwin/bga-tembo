define(['dojo', 'dojo/_base/declare', 'ebg/core/gamegui'], (dojo, declare) => {
  return declare('customgame.game', [ebg.core.gamegui], {
    /*
     * Constructor
     */
    constructor() {
      this._notifications = [];
      this._activeStates = [];
      this._connections = [];
      this._selectableNodes = [];

      this.canceledNotifFeature = false;
      this._notif_uid_to_log_id = {};
      this._notif_uid_to_mobile_log_id = {};
      this._last_notif = null;
    },

    getPlayerColor(pId) {
      return this.gamedatas.players[pId].color;
    },

    getColorRgb(playerId = this.gamedatas.active_player_id) {
      const rgb = this.hexToRgb(this.getPlayerColor(playerId));
      return `border-color: rgb(${rgb}); box-shadow: 0px 0px 5px rgba(${rgb}, 0.4)`;
    },

    /*
     * [Undocumented] Override BGA framework functions to call onLoadingComplete when loading is done
     */
    setLoader(value, max) {
      this.inherited(arguments);
      if (!this.isLoadingComplete && value >= 100) {
        this.isLoadingComplete = true;
        this.onLoadingComplete();
      }
    },

    onLoadingComplete() {
      debug('Loading complete');
      if (this.canceledNotifFeature) this.cancelLogs(this.gamedatas.canceledNotifIds);
    },

    /*
     * Setup:
     */
    setup(gamedatas) {
      // Create a new div for buttons to avoid BGA auto clearing it
      dojo.place("<div id='customActions' style='display:inline-block'></div>", $('generalactions'), 'after');
      dojo.place("<div id='restartAction' style='display:inline-block'></div>", $('customActions'), 'after');

      this.setupNotifications();
      dojo.connect(this.notifqueue, 'addToLog', () => {
        this.checkLogCancel(this._last_notif == null ? null : this._last_notif.msg.uid);
        this.addLogClass();
      });
    },

    /*
     * Detect if spectator or replay
     */
    isReadOnly() {
      return this.isSpectator || typeof g_replayFrom != 'undefined' || g_archive_mode;
    },

    /*
     * onEnteringState:
     * 	this method is called each time we are entering into a new game state.
     *
     * params:
     *  - str stateName : name of the state we are entering
     *  - mixed args : additional information
     */
    onEnteringState(stateName, args) {
      debug('Entering state: ' + stateName, args);

      if (this._activeStates.includes(stateName) && !this.isCurrentPlayerActive()) return;

      // Private state machine
      if (args.parallel) {
        if (args.args._private) this.setupPrivateState(args.args._private.state, args.args._private.args);
        return;
      }

      // Call appropriate method
      var methodName = 'onEnteringState' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
      if (this[methodName] !== undefined) this[methodName](args.args);
    },

    /**
     * Check change of activity
     */
    onUpdateActionButtons(stateName, args) {
      // Call appropriate method
      var methodName = 'onUpdateActivity' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
      if (this[methodName] !== undefined) this[methodName](args, status);
    },

    /*
     * Private state
     */
    setupPrivateState(state, args) {
      if (this.gamedatas.gamestate.parallel) delete this.gamedatas.gamestate.parallel;
      this.gamedatas.gamestate.name = state.name;
      this.gamedatas.gamestate.descriptionmyturn = state.descriptionmyturn;
      this.gamedatas.gamestate.possibleactions = state.possibleactions;
      this.gamedatas.gamestate.transitions = state.transitions;
      this.gamedatas.gamestate.args = args;
      this.updatePageTitle();
      this.onEnteringState(state.name, this.gamedatas.gamestate);
    },

    notif_newPrivateState(n) {
      this.onLeavingState(this.gamedatas.gamestate.name);
      this.setupPrivateState(n.args.state, n.args.args);
    },

    /**
     * onLeavingState:
     *    this method is called each time we are leaving a game state.
     *
     * params:
     *  - str stateName : name of the state we are leaving
     */
    onLeavingState(stateName) {
      debug('Leaving state: ' + stateName);
      this.clearPossible();
      dojo.query('.tmp').forEach((o) => this.destroy(o));
      if ($('colors-dial')) $('colors-dial').remove();
      this.updateZonesStatus(this.player_id);

      // Call appropriate method
      var methodName = 'onLeavingState' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
      if (this[methodName] !== undefined) this[methodName]();
    },

    clearPossible() {
      this.removeActionButtons();
      this.clearActionButtons();

      this._connections.forEach(dojo.disconnect);
      this._connections = [];
      this._selectableNodes.forEach((node) => {
        if ($(node)) dojo.removeClass(node, 'selectable selected');
      });
      this._selectableNodes = [];
      dojo.query('.unselectable').removeClass('unselectable');
      dojo.query('.selected').removeClass('selected');
    },

    /*
     * setupNotifications
     */
    setupNotifications() {
      this._notifications.forEach((notif) => {
        var functionName = 'notif_' + notif;

        let wrapper = async (args) => {
          let msg = this.formatString(this.format_string_recursive(args.log, args.args));
          if (msg != '') {
            this.clearPossible();

            $('gameaction_status').innerHTML = msg;
            $('pagemaintitletext').innerHTML = msg;
          }

          await this[functionName](args.args);
          if (args.args && args.args.infos) {
            this.updateInfosFromNotif(args.args.infos);
          }
          dojo.publish('notifEnd', null);
        };

        dojo.subscribe(notif, this, wrapper);
        this.notifqueue.setSynchronous(notif);
        this.notifqueue.setIgnoreNotificationCheck(notif, (n) => n.args.ignore && n.args.ignore == this.player_id);
      });
    },

    getVisibleTitleContainer() {
      function isVisible(elem) {
        return !!(elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length);
      }

      if (isVisible($('pagemaintitletext'))) {
        return $('pagemaintitletext');
      } else {
        return $('gameaction_status');
      }
    },

    /*
     * Add a blue/grey button if it doesn't already exists
     */
    addPrimaryActionButton(id, text, callback, zone = 'customActions') {
      if (!$(id)) this.addActionButton(id, text, callback, zone, false, 'blue');
    },

    addSecondaryActionButton(id, text, callback, zone = 'customActions') {
      if (!$(id)) this.addActionButton(id, text, callback, zone, false, 'gray');
    },

    addDangerActionButton(id, text, callback, zone = 'customActions') {
      if (!$(id)) this.addActionButton(id, text, callback, zone, false, 'red');
    },

    clearActionButtons() {
      dojo.empty('customActions');
      dojo.empty('restartAction');
    },

    slide(mobileElt, targetElt, options = {}) {
      let config = Object.assign(
        {
          duration: 1000,
          delay: 0,
          destroy: false,
          attach: true,
          changeParent: true, // Change parent during sliding to avoid zIndex issue
          pos: null,
          className: 'moving',
          from: null,
          clearPos: true,
          phantom: false,
          targetPos: 'last',
        },
        options
      );
      config.phantomStart = config.phantomStart || config.phantom;
      config.phantomEnd = config.phantomEnd || config.phantom;

      // Handle phantom at start
      mobileElt = $(mobileElt);
      let mobile = mobileElt;
      if (config.phantomStart) {
        mobile = dojo.clone(mobileElt);
        dojo.attr(mobile, 'id', mobileElt.id + '_animated');
        dojo.place(mobile, 'game_play_area');
        this.placeOnObject(mobile, mobileElt);
        dojo.addClass(mobileElt, 'phantom');
        config.from = mobileElt;
      }

      // Handle phantom at end
      targetElt = $(targetElt);
      let targetId = targetElt;
      if (config.phantomEnd) {
        targetId = dojo.clone(mobileElt);
        dojo.attr(targetId, 'id', mobileElt.id + '_afterSlide');
        dojo.addClass(targetId, 'phantom');
        dojo.place(targetId, targetElt, config.targetPos);
      }

      const newParent = config.attach ? targetId : $(mobile).parentNode;
      dojo.style(mobile, 'zIndex', 100);
      dojo.addClass(mobile, config.className);
      if (config.changeParent) this.changeParent(mobile, 'game_play_area');
      if (config.from != null) this.placeOnObject(mobile, config.from);
      return new Promise((resolve, _) => {
        const animation =
          config.pos == null
            ? this.slideToObject(mobile, targetId, config.duration, config.delay)
            : this.slideToObjectPos(mobile, targetId, config.pos.x, config.pos.y, config.duration, config.delay);

        dojo.connect(animation, 'onEnd', () => {
          dojo.style(mobile, 'zIndex', null);
          dojo.removeClass(mobile, config.className);
          if (config.phantomStart) {
            dojo.place(mobileElt, mobile, 'replace');
            dojo.removeClass(mobileElt, 'phantom');
            mobile = mobileElt;
          }
          if (config.changeParent) {
            if (config.phantomEnd) dojo.place(mobile, targetId, 'replace');
            else this.changeParent(mobile, newParent);
          }
          if (config.destroy) dojo.destroy(mobile);
          if (config.clearPos && !config.destroy)
            dojo.style(mobile, {
              top: null,
              left: null,
              position: null,
            });
          resolve();
        });
        animation.play();
      });
    },

    changeParent(mobile, new_parent, clearStyles = false) {
      if (mobile === null) {
        console.error('attachToNewParent: mobile obj is null');
        return;
      }
      if (new_parent === null) {
        console.error('attachToNewParent: new_parent is null');
        return;
      }
      if (typeof mobile === 'string') {
        mobile = $(mobile);
      }
      if (typeof new_parent === 'string') {
        new_parent = $(new_parent);
      }
      var src = dojo.position(mobile);
      dojo.style(mobile, 'position', 'absolute');
      dojo.place(mobile, new_parent, 'last');
      var tgt = dojo.position(mobile);
      var box = dojo.marginBox(mobile);
      var cbox = dojo.contentBox(mobile);
      var left = box.l + src.x - tgt.x;
      var top = box.t + src.y - tgt.y;
      this.positionObjectDirectly(mobile, left, top);
      box.l += box.w - cbox.w;
      box.t += box.h - cbox.h;
      if (clearStyles) {
        dojo.style(mobile, {
          top: null,
          left: null,
          position: null,
        });
      }
      return box;
    },

    positionObjectDirectly(mobileObj, x, y) {
      // do not remove this "dead" code some-how it makes difference
      dojo.style(mobileObj, 'left'); // bug? re-compute style
      // console.log("place " + x + "," + y);
      dojo.style(mobileObj, {
        left: x + 'px',
        top: y + 'px',
      });
      dojo.style(mobileObj, 'left'); // bug? re-compute style
    },

    place(tplMethodName, object, container) {
      if ($(container) === null) {
        console.error('Trying to place on null container', tplMethodName, object, container);
        return;
      }

      if (this[tplMethodName] === undefined) {
        console.error('Trying to create a non-existing template', tplMethodName);
        return;
      }

      return dojo.place(this[tplMethodName](object), container);
    },

    /*
     * Add a timer on an action button :
     * params:
     *  - buttonId : id of the action button
     *  - time : time before auto click, seconds
     */

    startActionTimer(buttonId, time) {
      var button = $(buttonId);
      var isReadOnly = this.isReadOnly();
      if (button === null || isReadOnly) {
        debug('Ignoring startActionTimer(' + buttonId + ')', 'readOnly=' + isReadOnly);
        return;
      }

      this._actionTimerLabel = button.innerHTML;
      this._actionTimerSeconds = time;
      this._actionTimerFunction = () => {
        var button = $(buttonId);
        if (button === null) {
          this.stopActionTimer();
        } else if (this._actionTimerSeconds-- > 1) {
          button.innerHTML = this._actionTimerLabel + ' (' + this._actionTimerSeconds + ')';
        } else {
          debug('Timer ' + buttonId + ' execute');
          button.click();
        }
      };
      dojo.connect($(buttonId), 'click', () => this.stopActionTimer());
      this._actionTimerFunction();
      this._actionTimerId = window.setInterval(this._actionTimerFunction, 1000);
      debug('Timer #' + this._actionTimerId + ' ' + buttonId + ' start');
    },

    stopActionTimer(buttonWithTimer = null) {
      if (this._actionTimerId != null) {
        debug('Timer #' + this._actionTimerId + ' stop');
        window.clearInterval(this._actionTimerId);
        delete this._actionTimerId;
      }
      if (buttonWithTimer) {
        $(buttonWithTimer).innerHTML = this._actionTimerLabel;
      }
    },

    /*
     * [Undocumented] Called by BGA framework on any notification message
     * Handle cancelling log messages for restart turn
     */
    onPlaceLogOnChannel(msg) {
      var currentLogId = this.notifqueue.next_log_id;
      var currentMobileLogId = this.next_log_id;
      var res = this.inherited(arguments);
      this._notif_uid_to_log_id[msg.uid] = currentLogId;
      this._notif_uid_to_mobile_log_id[msg.uid] = currentMobileLogId;
      this._last_notif = {
        logId: currentLogId,
        mobileLogId: currentMobileLogId,
        msg,
      };
      return res;
    },

    /*
     * cancelLogs:
     *   strikes all log messages related to the given array of notif ids
     */
    checkLogCancel(notifId) {
      if (this.gamedatas.canceledNotifIds != null && this.gamedatas.canceledNotifIds.includes(notifId)) {
        this.cancelLogs([notifId]);
      }
    },

    cancelLogs(notifIds) {
      notifIds.forEach((uid) => {
        if (this._notif_uid_to_log_id.hasOwnProperty(uid)) {
          let logId = this._notif_uid_to_log_id[uid];
          if ($('log_' + logId)) dojo.addClass('log_' + logId, 'cancel');
        }
        if (this._notif_uid_to_mobile_log_id.hasOwnProperty(uid)) {
          let mobileLogId = this._notif_uid_to_mobile_log_id[uid];
          if ($('dockedlog_' + mobileLogId)) dojo.addClass('dockedlog_' + mobileLogId, 'cancel');
        }
      });
    },

    addLogClass() {
      if (this._last_notif == null) return;

      let notif = this._last_notif;
      let type = notif.msg.type;
      if (type == 'history_history') type = notif.msg.args.originalType;

      if ($('log_' + notif.logId)) {
        dojo.addClass('log_' + notif.logId, 'notif_' + type);

        var methodName = 'onAdding' + type.charAt(0).toUpperCase() + type.slice(1) + 'ToLog';
        if (this[methodName] !== undefined) this[methodName](notif);
      }
      if ($('dockedlog_' + notif.mobileLogId)) {
        dojo.addClass('dockedlog_' + notif.mobileLogId, 'notif_' + type);
      }
    },

    getLogIcons(list) {
      return list
        .map((resource) => {
          return this.getLogIcon(resource);
        })
        .join(', ');
    },

    coloredPlayerName(id, name = '') {
      const player = this.gamedatas.players[id];
      if (player === undefined) return name;

      const color = player.color;
      return '<!--PNS--><span class="playername" style="color:#' + color + '">' + player.name + '</span><!--PNE-->';
    },

    getLogIcon(type) {
      return this.format_block('jstpl_resource_icon_log', { type: type });
    },

    querySingle(query, element = null) {
      return dojo.query(query, element)[0];
    },

    destroyAll(locators) {
      if (!Array.isArray(locators)) {
        locators = [locators];
      }
      if (locators) {
        locators.forEach((locator) => {
          dojo.query(locator).forEach((item) => {
            dojo.destroy(item);
          });
        });
      }
    },

    waitForDisappearance(locator) {
      return new Promise(function (resolve, reject) {
        (function waitFor() {
          if (dojo.query(locator).length === 0) {
            resolve();
          } else {
            setTimeout(waitFor.bind(this, resolve), 100);
          }
        })();
      });
    },

    dojoConnect(element, func) {
      // const connection = dojo.connect($(element), 'click', (evt) => {
      //   evt.preventDefault();
      //   evt.stopPropagation();
      //   func(evt);
      // });
      // this._connections.push(connection);

      const connectionDown = dojo.connect($(element), 'pointerdown', (evt) => {
        evt.preventDefault();
        evt.stopPropagation();
        this._pointerDownElt = $(element);
      });
      this._connections.push(connectionDown);

      const connectionUp = dojo.connect($(element), 'pointerup', (evt) => {
        evt.preventDefault();
        evt.stopPropagation();
        if ($(element) == this._pointerDownElt) {
          func(evt);
        }
      });
      this._connections.push(connectionUp);
    },

    addClass(element, clazz, removeAfter = false, delay = 1000) {
      dojo.addClass(element, clazz);
      if (removeAfter) {
        setTimeout(() => {
          dojo.removeClass(element, clazz);
        }, delay);
      }
    },

    forEachPlayer(callback) {
      this.getOrderedPlayers().forEach(callback);
    },

    getOrderedPlayers(except) {
      const otherPlayers = [];
      let playersIds;
      if (this.gamedatas.playerorder.length === Object.keys(this.gamedatas.players).length) {
        playersIds = this.gamedatas.playerorder;
      } else {
        const sortedPlayers = Object.values(this.gamedatas.players).sort((a, b) => a.no - b.no);
        playersIds = sortedPlayers.map((player) => {
          return player.id;
        });
      }
      playersIds.forEach((pId) => {
        pId = parseInt(pId);
        if (except === null || pId !== except) {
          otherPlayers.push(this.gamedatas.players[pId]);
        }
      });
      return otherPlayers;
    },

    addSelectableClass(elements) {
      this.addSelectableSelectedClass(elements, 'selectable');
    },

    addSelectedClass(elements) {
      this.addSelectableSelectedClass(elements, 'selected');
    },

    addSelectableSelectedClass(elements, clazz) {
      if (!Array.isArray(elements)) {
        elements = [elements];
      }
      elements.forEach((element) => {
        dojo.addClass(element, clazz);
        this._selectableNodes.push(element);
      });
    },

    addUnselectableClass(elements) {
      if (!Array.isArray(elements)) {
        elements = [elements];
      }
      elements.forEach((element) => {
        dojo.addClass(element, 'unselectable');
      });
    },

    makeAllSelectableAndClickable(elements, callback) {
      this.addSelectableClass(elements);
      elements.forEach((element) => {
        this.dojoConnect(element, () => callback(element));
      });
    },

    makeAllSelectedAndClickable(elements, callback) {
      this.addSelectedClass(elements);
      elements.forEach((element) => {
        this.dojoConnect(element, () => callback(element));
      });
    },

    fadeOutAndDestroyAll(locators, duration = 600) {
      const promises = [];
      if (!Array.isArray(locators)) {
        locators = [locators];
      }
      locators.forEach((locator) => {
        dojo.query(locator).forEach((item) => {
          this.fadeOutAndDestroy(item, duration);
          dojo.addClass(item, 'destroying');
        });
        promises.push(this.waitForDisappearance(locator));
      });
      return Promise.all(promises);
    },

    async fadeOutAndDestroy(element, duration) {
      dojo.addClass(element, 'fadeout');
      await new Promise((resolve) => setTimeout(resolve, duration));
      dojo.destroy(element);
    },

    toggleHelpMode(b) {
      if (b) this.activateHelpMode();
      else this.desactivateHelpMode();
    },

    activateHelpMode() {
      this._helpMode = true;
      dojo.addClass('ebd-body', 'help-mode');
      this._displayedTooltip = null;
      document.body.addEventListener('click', this.closeCurrentTooltip.bind(this));
    },

    desactivateHelpMode() {
      this.closeCurrentTooltip();
      this._helpMode = false;
      dojo.removeClass('ebd-body', 'help-mode');
      document.body.removeEventListener('click', this.closeCurrentTooltip.bind(this));
    },

    closeCurrentTooltip() {
      if (!this._helpMode) return;

      if (this._displayedTooltip == null) return;
      else {
        this._displayedTooltip.close();
        this._displayedTooltip = null;
      }
    },

    addCustomTooltip(id, html, config = {}) {
      config = Object.assign(
        {
          delay: 400,
          midSize: true,
          forceRecreate: false,
        },
        config
      );

      // Handle dynamic content out of the box
      let getContent = () => {
        let content = typeof html === 'function' ? html() : html;
        if (config.midSize) {
          content = '<div class="midSizeDialog">' + content + '</div>';
        }
        return content;
      };

      if (this.tooltips[id] && !config.forceRecreate) {
        this.tooltips[id].getContent = getContent;
        return;
      }

      let tooltip = new dijit.Tooltip({
        //        connectId: [id],
        getContent,
        position: this.defaultTooltipPosition,
        showDelay: config.delay,
      });
      this.tooltips[id] = tooltip;
      dojo.addClass(id, 'tooltipable');
      dojo.place(
        `<div class='help-marker'>
            <svg><use href="#help-marker-svg" /></svg>
          </div>`,
        id
      );

      tooltip.clickCallback = (evt) => {
        if (!this._helpMode) {
          if (tooltip.showTimeout != null) clearTimeout(tooltip.showTimeout);
          tooltip.close();
        } else {
          evt.stopPropagation();

          if (tooltip.state == 'SHOWING') {
            this.closeCurrentTooltip();
          } else {
            this.closeCurrentTooltip();
            tooltip.open($(id));
            this._displayedTooltip = tooltip;
          }
        }
      };
      dojo.connect($(id), 'click', tooltip.clickCallback.bind(this));

      tooltip.showTimeout = null;
      tooltip.hoverCallback = (evt) => {
        evt.stopPropagation();
        if (!this._helpMode && !this._dragndropMode) {
          if (tooltip.showTimeout != null) clearTimeout(tooltip.showTimeout);

          tooltip.showTimeout = setTimeout(() => {
            if ($(id)) tooltip.open($(id));
          }, config.delay);
        }
      };
      dojo.connect($(id), 'mouseenter', tooltip.hoverCallback.bind(this));

      dojo.connect($(id), 'mouseleave', (evt) => {
        evt.stopPropagation();
        if (!this._helpMode && !this._dragndropMode) {
          tooltip.close();
          if (tooltip.showTimeout != null) clearTimeout(tooltip.showTimeout);
        }
      });
    },

    onClick(node, callback, temporary = true) {
      let safeCallback = (evt) => {
        evt.stopPropagation();
        if (this.isInterfaceLocked()) return false;
        if (this._helpMode) return false;
        callback(evt);
      };

      if (temporary) {
        this.dojoConnect($(node), safeCallback);
        dojo.removeClass(node, 'unselectable');
        dojo.addClass(node, 'selectable');
        this._selectableNodes.push(node);
      } else {
        dojo.connect($(node), 'click', safeCallback);
      }
    },

    clientState(name, descriptionmyturn, args) {
      this.setClientState(name, {
        descriptionmyturn,
        description: descriptionmyturn,
        args,
      });
    },

    addCancelStateBtn(text = null) {
      if (text == null) {
        text = _('Cancel');
      }

      this.addSecondaryActionButton('btnCancel', text, () => this.restoreServerGameState(), 'restartAction');
    },

    destroy(elem) {
      if (this.tooltips[elem.id]) {
        this.tooltips[elem.id].destroy();
        delete this.tooltips[elem.id];
      }

      elem.remove();
    },

    /**
     * Own counter implementation that works with replay
     */
    createCounter(id, defaultValue = 0, linked = null) {
      if (!$(id)) {
        console.error('Counter : element does not exist', id);
        return null;
      }

      let game = this;
      let o = {
        span: $(id),
        linked: linked ? $(linked) : null,
        targetValue: 0,
        currentValue: 0,
        speed: 100,
        getValue() {
          return this.targetValue;
        },
        setValue(n) {
          this.currentValue = +n;
          this.targetValue = +n;
          this.span.innerHTML = +n;
          if (this.linked) this.linked.innerHTML = +n;
        },
        toValue(n) {
          if (game.instantaneousMode) {
            this.setValue(n);
            return;
          }

          this.targetValue = +n;
          if (this.currentValue != n) {
            this.span.classList.add('counter_in_progress');
            setTimeout(() => this.makeCounterProgress(), this.speed);
          }
        },
        goTo(n, anim) {
          if (anim) this.toValue(n);
          else this.setValue(n);
        },
        incValue(n) {
          let m = +n;
          this.toValue(this.targetValue + m);
        },
        makeCounterProgress() {
          if (this.currentValue == this.targetValue) {
            setTimeout(() => this.span.classList.remove('counter_in_progress'), this.speed);
            return;
          }

          let step = Math.ceil(Math.abs(this.targetValue - this.currentValue) / 5);
          this.currentValue += (this.currentValue < this.targetValue ? 1 : -1) * step;
          this.span.innerHTML = this.currentValue;
          if (this.linked) this.linked.innerHTML = this.currentValue;
          setTimeout(() => this.makeCounterProgress(), this.speed);
        },
      };
      o.setValue(defaultValue);
      return o;
    },
  });
});
