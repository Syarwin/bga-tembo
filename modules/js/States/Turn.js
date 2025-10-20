define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const LOCATION_TABLE = 'table';
  const COLORS_FULL_TYPE = {
    b: 'flower-blue',
    y: 'flower-yellow',
    r: 'flower-red',
    w: 'flower-white',
    g: 'flower-grey',
    j: 'flower-joker',
  };
  const COLOR_ANIMAL_MAP = {
    b: 'cassowary',
    y: 'tiger',
    r: 'orangutan',
    w: 'rhinoceros',
    g: 'hornbill',
  };

  function onlyUnique(value, index, array) {
    return array.indexOf(value) === index;
  }

  return declare('tembo.turn', null, {
    constructor() {
      this._notifications.push('newTurn');
      this._notifications.push('flowerCardChosen');
      this._notifications.push('meeplePlaced');
      this._notifications.push('animalPlaced');
      this._notifications.push('discardLeftoverFlowerCards');
      this._notifications.push('plannedTurn');
      this._notifications.push('cancelPlannedTurn');
    },

    async notif_newTurn(args) {
      debug('Notif: starting new turn', args);
      this.gamedatas.turn = args.turn;
      this.updateTurnNumber();

      await Promise.all(
        args.cards.map((card, i) => {
          this.addFlowerCard(card, this.getVisibleTitleContainer());
          return this.slide(`flower-card-${card.id}`, $('flower-cards-holder'), {
            delay: 100 * i,
            phantomEnd: true,
          });
        })
      );
    },

    /////////////////////////////////////////////////////////
    // Initial entry point => choose a card or the pangolin
    /////////////////////////////////////////////////////////
    onEnteringStateTurn(args) {
      if (this.isSpectator) return;

      // Is the player inactive ?
      if (!this.isCurrentPlayerActive()) {
        // Is the player currently planning their next turn?
        if (args.planTurn) {
          this.addCancelStateBtn();
        }
        // Otherwise, offer them to plan their next turn only if they have one
        else {
          if (args.remainingPIds.includes(this.player_id)) {
            // Have they planned something already?
            if (args._private) {
              this.addSecondaryActionButton('btnCancel', _('Cancel planned turn'), () => {
                this.bgaPerformAction('actCancelPlan', {}, { checkAction: false });
              });
              this.highlightOngoingMoves(args._private);
            }
            // Fresh planification
            else {
              this.addPrimaryActionButton('btnPlan', _('Plan next turn'), () => {
                args.planTurn = true;
                this.clientState('turn', _('You must choose a Flower card and place the corresponding Flowers'), args);
              });
            }
          }
          return;
        }
      }

      // Cards
      let cardIds = args.cards[this.player_id];
      cardIds.forEach((cardId) => {
        this.onClick(`flower-card-${cardId}`, () => {
          let colors = $(`flower-card-${cardId}`).dataset.type.split('');
          let data = { cardId, colors, flowers: {}, flowersOrder: [] };

          // Joker card => select the color first
          if (colors.length === 1) {
            this.clientState('chooseFlowerCardColor', _('What flower do you want to place?'), data);
          }
          // Standard case => go to place flower client state
          else {
            this.clientState('placeFlowers', _('You must place the flowers on your board'), data);
          }
        });
      });

      // Pangolin
      let canTakePangolin = args.pangolin === LOCATION_TABLE;
      if (args.planTurn && !args.pangolinPlayed) canTakePangolin = true;

      if (canTakePangolin) {
        let callbackPangolin = () => {
          let data = { cardId: 0, flowers: {}, flowersOrder: [] };
          this.clientState('chooseFlowerCardColor', _('Which flower do you want to place?'), data);
        };

        this.addPrimaryActionButton('pangolin', _('Take Pangolin'), callbackPangolin);
        this.onClick('meeple-pangolin', callbackPangolin);
      }

      // No move left
      if (cardIds.length == 0 && !canTakePangolin) {
        this.clientState(
          'impossibleMove',
          _("You can't play any flower card, please select the one you want to discard instead"),
          {}
        );
      }
    },

    notif_plannedTurn(args) {
      debug('Notif: planned turn', args);
      this.last_server_state.args._private = args.turn;
      this.restoreServerGameState();
    },

    notif_cancelPlannedTurn(args) {
      debug('Notif: cancel planned turn', args);
      delete this.gamedatas.gamestate.args._private;
      delete this.last_server_state.args._private;
      this.restoreServerGameState();
    },

    // No move possible
    onEnteringStateImpossibleMove() {
      $('flower-cards-holder')
        .querySelectorAll('.tembo-flower-card')
        .forEach((oCard) => {
          this.onClick(oCard, () => {
            this.clientState('confirmTurn', _('Please confirm your discard'), {
              cardId: oCard.dataset.id,
              flowers: {},
              flowersOrder: [],
              discard: true,
            });
          });
        });
    },

    // Notif choose card
    async notif_flowerCardChosen(args) {
      debug('Notif: flowerCardChosen', args);
      // Pangolin token
      if (args.flowerCardId === 0) {
        this.gamedatas.pangolin = args.player_id;
        await this.slide('meeple-pangolin', $(`pangolin-${args.player_id}`));
      }
      // Flower card
      else {
        await this.slide(`flower-card-${args.flowerCardId}`, this.getVisibleTitleContainer(), { destroy: true });
      }
    },

    /////////////////////////////////////////////////////////
    // Display the ongoing choices (card, flowers, ...)
    /////////////////////////////////////////////////////////
    highlightOngoingMoves(args) {
      // TODO : improve client state to make it work like a stack
      this.addCancelStateBtn();

      // Remove colors dial
      if ($('colors-dial')) $('colors-dial').remove();

      // Highlight card //
      let cardId = args.cardId;
      // Pangolin
      if (cardId == 0) {
        $('meeple-pangolin').classList.add('selected');
      }
      // Normal flower card
      else {
        let oCard = $(`flower-card-${cardId}`);
        if (oCard) oCard.classList.add('selected');
      }

      // Place temporary flowers
      Object.entries(args.flowers).forEach(([i, cell]) => {
        let oCell = this.getCell(cell);
        let o;

        // Already something here ? => Place a tree instead
        if (oCell.querySelector('.tembo-meeple')) {
          // Animal placed?
          if (args.animal && args.animal == i) {
            o = this.addMeeple({ id: `tmp-${i}`, type: `animal-${COLOR_ANIMAL_MAP[args.colors[i]]}` }, oCell);
          } else {
            o = this.addMeeple({ id: `tmp-${i}`, type: `tree-${2 * ((parseInt(i) % 4) + 1)}` }, oCell);
          }
        }
        // Otherwise, basic flow
        else {
          o = this.addMeeple({ id: `tmp-${i}`, type: args.colors[i] }, oCell);
        }

        o.classList.add('tmp');
      });

      // Place temporary flowers from fertilization
      if (args.fertilized) {
        Object.entries(args.fertilized).forEach(([i, cell]) => {
          let oCell = this.getCell(cell);
          let o;

          // Already something here ? => Place a tree instead
          if (oCell.querySelector('.tembo-meeple')) {
            o = this.addMeeple({ id: `tmp-fertilize-${i}`, type: `tree-${2 * ((parseInt(i) % 4) + 1)}` }, oCell);
          }
          // Otherwise, basic flow
          else {
            o = this.addMeeple({ id: `tmp-fertilize-${i}`, type: cell.color }, oCell);
          }

          o.classList.add('tmp');
        });
      }

      this.updateZonesStatus(this.player_id);
    },

    /////////////////////////////////////////////////////////
    // Choose the color : for the pangolin and some cards
    /////////////////////////////////////////////////////////
    onEnteringStateChooseFlowerCardColor(args) {
      this.highlightOngoingMoves(args);

      let colorsByCells = {};
      Object.keys(COLORS_FULL_TYPE).forEach((type, i) => {
        // Ignore joker color
        if (type == 'j') return;

        let icon = this.formatIcon(COLORS_FULL_TYPE[type]);
        this.addSecondaryActionButton(`flower${i}`, icon, () => {
          args.colors = [type];
          args.i = 0;
          this.clientState('placeFlower', _('Where do you want to place that flower?') + icon, args);
        });
        $(`flower${i}`).classList.add('flowerBtn');

        let color = type;
        let cells = this.getFlowerValidPosition(color, args.flowers);
        cells.forEach((cell) => {
          let uid = `${cell.x}-${cell.y}`;
          if (!colorsByCells[uid]) colorsByCells[uid] = [];
          if (!colorsByCells[uid].includes(color)) colorsByCells[uid].push(color);
        });
      });

      // Allow to click on cell directly
      Object.entries(colorsByCells).forEach(([cellUId, colors]) => {
        let cell = this.extractCellFromUId(cellUId);
        let oCell = this.getCell(cell);
        this.onClick(oCell, () => {
          if ($('colors-dial')) $('colors-dial').remove();

          // Only one color can be placed here => autoplace
          if (colors.length == 1) {
            args.colors = colors;
            this.auxPlaceFlower(args, 0, cell);
          }
          // Display a dial
          else {
            oCell.insertAdjacentHTML('beforeend', `<div id="colors-dial"></div>`);
            colors.forEach((color) => {
              $('colors-dial').insertAdjacentHTML(
                'beforeend',
                `<div id='dial-${color}' class='dial-selector'>${this.formatIcon(COLORS_FULL_TYPE[color])}</div>`
              );
              this.onClick(`dial-${color}`, () => {
                args.colors = [color];
                this.auxPlaceFlower(args, 0, cell);
              });
            });
            $('colors-dial').dataset.n = colors.length;
          }
        });
      });
    },

    /////////////////////////////////////////////////////////
    // Place the flowers
    /////////////////////////////////////////////////////////

    // Choose the flower you want to place
    onEnteringStatePlaceFlowers(args) {
      this.highlightOngoingMoves(args);

      // Callback once we picked the color we want to place
      let callback = (i, isPlaced) => {
        if (isPlaced) return () => {};
        else
          return () => {
            let icon = this.formatIcon(COLORS_FULL_TYPE[args.colors[i]]);
            args.i = i;
            this.clientState('placeFlower', _('Where do you want to place that flower?') + icon, args);
          };
      };

      let remainingColors = {};
      let colorsByCells = {};
      for (let i = 0; i < args.colors.length; i++) {
        let color = args.colors[i];
        let icon = this.formatIcon(COLORS_FULL_TYPE[color]);
        let isPlaced = args.flowers[i] !== undefined;
        this.addSecondaryActionButton(`flower${i}`, icon, callback(i, isPlaced));
        $(`flower${i}`).classList.add('flowerBtn');
        $(`flower${i}`).classList.toggle('placed', isPlaced);

        if (!isPlaced) {
          remainingColors[i] = color;
          let cells = this.getFlowerValidPosition(color, args.flowers);
          cells.forEach((cell) => {
            let uid = `${cell.x}-${cell.y}`;
            if (!colorsByCells[uid]) colorsByCells[uid] = [];
            if (!colorsByCells[uid].includes(color)) colorsByCells[uid].push(color);
          });
        }
      }

      // Auto select if only one color type left
      if (Object.values(remainingColors).filter(onlyUnique).length === 1) {
        let i = Object.keys(remainingColors)[0];
        callback(i, false)();
        return;
      }

      // Allow to click on cell
      Object.entries(colorsByCells).forEach(([cellUId, colors]) => {
        let cell = this.extractCellFromUId(cellUId);
        let oCell = this.getCell(cell);
        this.onClick(oCell, () => {
          if ($('colors-dial')) $('colors-dial').remove();

          // Only one color can be placed here => autoplace
          if (colors.length == 1) {
            let color = colors[0];
            let i = Object.keys(remainingColors).find((key) => remainingColors[key] === color);
            this.auxPlaceFlower(args, i, cell);
          }
          // Display a dial
          else {
            oCell.insertAdjacentHTML('beforeend', `<div id="colors-dial"></div>`);
            colors.forEach((color) => {
              let i = Object.keys(remainingColors).find((key) => remainingColors[key] === color);
              $('colors-dial').insertAdjacentHTML(
                'beforeend',
                `<div id='dial-${color}' class='dial-selector'>${this.formatIcon(COLORS_FULL_TYPE[color])}</div>`
              );
              this.onClick(`dial-${color}`, () => this.auxPlaceFlower(args, i, cell));
            });
            $('colors-dial').dataset.n = colors.length;
          }
        });
      });
    },

    auxPlaceFlower(args, i, cell) {
      args.flowers[i] = cell;
      args.flowersOrder.push(i);
      let isFinished = Object.values(args.flowers).length === args.colors.length;
      if (isFinished) {
        this.clientState('placeAnimal', _('You may place an animal'), args);
      } else {
        this.clientState('placeFlowers', _('You must place the flowers on your board'), args);
      }
    },

    // Place indivual flower
    onEnteringStatePlaceFlower(args) {
      this.highlightOngoingMoves(args);

      let cells = this.getFlowerValidPosition(args.colors[args.i], args.flowers);
      cells.forEach((cell) => {
        this.onClick(this.getCell(cell), () => {
          this.auxPlaceFlower(args, args.i, cell);
        });
      });
    },

    // Notif flower placed
    async notif_meeplePlaced(args) {
      debug('Notif: meeplePlaced', args);

      let meeple = args.meeple;
      let oMeeple = this.addMeeple(meeple, this.getVisibleTitleContainer());
      let cell = this.getCell(meeple, args.player_id);
      await this.slide(oMeeple, cell);
      if (args.player_id === this.player_id) {
        this._board[meeple.x][meeple.y].push(meeple);
        this._emptyBoard = false;
      }

      let tmpMeeple = cell.querySelector('.tmp');
      if (tmpMeeple) this.destroy(tmpMeeple);
      this.updateZonesStatus(args.player_id);
    },

    /////////////////////////////////////////////////////////
    // Animal
    /////////////////////////////////////////////////////////
    onEnteringStatePlaceAnimal(args) {
      this.highlightOngoingMoves(args);

      let zones = this.gamedatas.board.zones,
        cellsZone = this.gamedatas.board.cellsZone;

      // Try to find a complete zone
      let completeZones = {};
      Object.entries(args.flowers).forEach(([i, cell]) => {
        let zoneId = cellsZone[cell.x][cell.y];

        /// Check if the zone if full by checking how many meeples are there
        let isFullAndValid = true,
          color = null;
        zones[zoneId].cells.forEach((cell2) => {
          let oMeeples = this.getCell(cell2).childNodes;
          if (oMeeples.length < 2) isFullAndValid = false;
          if (oMeeples.length > 0) {
            let cellColor = oMeeples[0].getAttribute('data-type');
            if (color === null) color = cellColor;
            else if (color !== cellColor) isFullAndValid = false;
          }
        });

        if (isFullAndValid) {
          /// Any animal left of this type?
          console.log(args.colors[i], COLOR_ANIMAL_MAP);
          let animalType = COLOR_ANIMAL_MAP[args.colors[i]];
          debug(`animal-reserve-animal-${animalType}-counter`);
          let counter = $(`animal-reserve-animal-${animalType}-counter`);
          if (parseInt(counter.innerHTML) > 0) {
            completeZones[i] = zoneId; // Store the index to replace the tree by the animal
          }
        }
      });

      // No full zone => auto skip to confirm
      if (Object.keys(completeZones).length === 0) {
        this.clientState('confirmTurn', _('Please confirm your turn'), args);
      }
      // Otherwise, let the user click on the cell
      else {
        Object.entries(completeZones).forEach(([i, zoneId]) => {
          let cell = args.flowers[i];
          this.onClick(this.getCell(cell), () => {
            args.animal = i;
            args.animalZone = zoneId;
            args.fertilized = {};
            this.clientState('fertilize', _('You may fertilize adjacent spaces'), args);
          });
        });

        this.addDangerActionButton('pass', _('Pass'), () =>
          this.confirmationDialog(
            _("Are you sure you don't want to place an animal? You won't be able to place one in this perfect habitat(s) later."),
            () => this.clientState('confirmTurn', _('Please confirm your turn'), args)
          )
        );
      }
    },

    async notif_animalPlaced(args) {
      debug('Notif: animal placed', args);

      let animal = args.animal;
      let cell = this.getCell(animal, args.player_id);
      await Promise.all([
        this.slide(`meeple-${animal.id}`, cell),
        this.slide(`meeple-${args.tree.id}`, this.getVisibleTitleContainer(), { destroy: true }),
      ]);
      if (this.player_id == args.player_id) {
        this._board[animal.x][animal.y].pop();
        this._board[animal.x][animal.y].push(animal);
      }

      let tmpMeeple = cell.querySelector('.tmp');
      if (tmpMeeple) this.destroy(tmpMeeple);
      this.updateZonesStatus(args.player_id);
    },

    /////////////////////////////////////////////////////////
    // Fertilize
    /////////////////////////////////////////////////////////
    onEnteringStateFertilize(args) {
      this.highlightOngoingMoves(args);

      // Cell where the animal was placed
      let cell = args.flowers[args.animal];
      let cells = { ...this.getFertizableCells(cell, args.flowers) };
      console.log(cells);

      // Remove the already fertilized one
      Object.keys(args.fertilized).forEach((i) => delete cells[i]);

      // Nothing else to fertilize => auto skip to confirm
      if (Object.keys(cells).length === 0) {
        this.clientState('confirmTurn', _('Please confirm your turn'), args);
      }
      // Otherwise, let the user click on the cell
      else {
        Object.entries(cells).forEach(([i, cell]) => {
          this.onClick(this.getCell(cell), () => {
            args.fertilizeIndex = i;
            args.fertilizeCell = cell;
            this.clientState('fertilizeChooseColor', _('Which flower do you want to place?'), args);
          });
        });

        this.addDangerActionButton('pass', _('Pass'), () =>
          this.confirmationDialog(_("Are you sure you don't want to fertilize the other place(s)?"), () =>
            this.clientState('confirmTurn', _('Please confirm your turn'), args)
          )
        );
      }
    },

    /////////////////////////////////////////////////////////
    // Fertilize choose color
    /////////////////////////////////////////////////////////
    onEnteringStateFertilizeChooseColor(args) {
      this.highlightOngoingMoves(args);

      let oCell = this.getCell(args.fertilizeCell);
      oCell.classList.add('selected');

      let callback = (color) => {
        args.fertilized[args.fertilizeIndex] = {
          x: args.fertilizeCell.x,
          y: args.fertilizeCell.y,
          color,
        };
        this.clientState('fertilize', _('You may fertilize adjacent spaces'), args);
      };

      // Only one color => auto select
      if (args.fertilizeCell.colors.length === 1) {
        callback(args.fertilizeCell.colors[0]);
      }
      // Otherwise, create buttons
      else {
        oCell.insertAdjacentHTML('beforeend', `<div id="colors-dial"></div>`);
        args.fertilizeCell.colors.forEach((type, i) => {
          let icon = this.formatIcon(COLORS_FULL_TYPE[type]);
          this.addSecondaryActionButton(`flower${i}`, icon, () => callback(type));
          $(`flower${i}`).classList.add('flowerBtn');

          let color = type;
          $('colors-dial').insertAdjacentHTML('beforeend', `<div id='dial-${color}' class='dial-selector'>${icon}</div>`);
          this.onClick(`dial-${color}`, () => callback(type));
        });
        $('colors-dial').dataset.n = args.fertilizeCell.colors.length;
      }
    },

    /////////////////////////////////////////////////////////
    // Confirm the whole turn
    /////////////////////////////////////////////////////////
    onEnteringStateConfirmTurn(args) {
      this.highlightOngoingMoves(args);

      delete args.fertilizeCell;
      delete args.fertilizeIndex;
      delete args.i;

      let action = this.isCurrentPlayerActive() ? 'actTakeTurn' : 'actPlanTurn';
      this.addPrimaryActionButton('btnConfirm', _('Confirm'), () =>
        this.bgaPerformAction(action, { turn: JSON.stringify(args) }, { checkAction: this.isCurrentPlayerActive() })
      );
    },

    async notif_discardLeftoverFlowerCards(args) {
      debug('Notif: Discard Leftover Flower Cards', args);

      let cards = [...$('flower-cards-container').querySelectorAll('.tembo-flower-card')];
      await Promise.all(
        cards.map((oCard, i) => this.slide(oCard, this.getVisibleTitleContainer(), { destroy: true, delay: 100 * i }))
      );
    },
  });
});
