define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const LOCATION_TABLE = 'table';

  return declare('tembo.phaseOne', null, {
    // constructor() {
    //   this._notifications.push(['flowerCardChosen', 1]);
    //   this._notifications.push(['flowerPlaced', 1]);
    //   this._notifications.push(['treePlaced', 1]);
    // },

    onEnteringStateChooseFlowerCard(args) {
      this.destroyAll('.tembo-flower-card');
      const cards = this.placeFlowerCards(args.cards);
      if (this.isCurrentPlayerActive()) {
        this.makeAllSelectableAndClickable(cards, (card) => {
          const id = this.extractId(card, 'flower-card');
          this.bgaPerformAction('actChooseFlowerCard', { id: id });
        });
        if (this.gamedatas.pangolin === LOCATION_TABLE) {
          this.addPrimaryActionButton('pangolin', `Take Pangolin`, () => {
            this.bgaPerformAction('actChooseFlowerCard', { id: 0 });
          });
        }
      }
    },

    placeFlowerCards(cards) {
      return cards.map((card) => {
        if (!$(`flower-card-${card.id}`)) {
          this.addFlowerCard(card);
        }

        let o = $(`flower-card-${card.id}`);
        if (!o) return null;

        let container = this.getFlowerCardContainer(card);
        if (o.parentNode !== $(container)) {
          dojo.place(o, container);
        }

        return o;
      });
    },

    onEnteringStateChooseFlowerColor(args) {
      args.colors.forEach((color) => {
        this.addPrimaryActionButton(color, this.tplFlowerIcon(color, true), (element) => {
          this.bgaPerformAction('actChooseFlowerColor', { colorClass: color });
        });
      });
    },

    onEnteringStatePlaceFlowers(args) {
      if (this.isCurrentPlayerActive()) {
        const flowersColors = args.flowersClasses;
        const flowersElements = flowersColors.map((flower) => {
          return this.tplFlowerIcon(flower, true);
        });

        // *** All this block should be replaced with the client logic. Here are all possible correct and incorrect placements
        const x = 0;
        const y = 2;
        if (flowersColors.length === 1) {
          this.addPrimaryActionButton('one', `${flowersElements[0]} -> ${x},${y}`, () => {
            const flowerObject = this.getFlowerObject(flowersColors[0], x, y);
            this.bgaPerformAction('actPlaceFlowers', { flowers: JSON.stringify([flowerObject]) });
          });
          this.addPrimaryActionButton('incorr', `Incorrect amount`, () => {
            const flowerObject = this.getFlowerObject(flowersColors[0], x, y);
            const fakeObject = this.getFlowerObject(flowersColors[0], 0, 1);
            this.bgaPerformAction('actPlaceFlowers', { flowers: JSON.stringify([flowerObject, fakeObject]) });
          });
        }

        if (flowersColors.length > 1) {
          this.addPrimaryActionButton('incorrectcolor', `Incorrect color`, () => {
            const incorrectColor = flowersColors[0] === 'icon-flower-red' ? 'icon-flower-blue' : 'icon-flower-red';
            const flowers = [this.getFlowerObject(incorrectColor, x, y), this.getFlowerObject(flowersColors[1], x + 1, y)];
            if (flowersColors.length > 2) {
              flowers.push(this.getFlowerObject(flowersColors[2], x + 2, y));
            }
            this.bgaPerformAction('actPlaceFlowers', { flowers: JSON.stringify(flowers) });
          });
          this.addPrimaryActionButton('onenotadjacent', `One not adjacent`, () => {
            const flowers = [this.getFlowerObject(flowersColors[0], x, y), this.getFlowerObject(flowersColors[1], x + 3, y)];
            if (flowersColors.length > 2) {
              flowers.push(this.getFlowerObject(flowersColors[2], x + 2, y));
            }
            this.bgaPerformAction('actPlaceFlowers', { flowers: JSON.stringify(flowers) });
          });
          if (flowersColors[0] === flowersColors[1]) {
            this.addPrimaryActionButton('two-same', `Two same to same coords`, () => {
              const flowers = [this.getFlowerObject(flowersColors[0], x, y), this.getFlowerObject(flowersColors[1], x, y)];
              this.bgaPerformAction('actPlaceFlowers', { flowers: JSON.stringify(flowers) });
            });
          }
          this.addPrimaryActionButton('Allcorrect', `All correct`, () => {
            const flowerObject0 = this.getFlowerObject(flowersColors[0], x, y);
            const flowerObject1 = this.getFlowerObject(flowersColors[1], x + 1, y);
            const flowers = [flowerObject0, flowerObject1];
            if (flowersColors.length > 2) {
              flowers.push(this.getFlowerObject(flowersColors[2], x + 2, y));
            }
            this.bgaPerformAction('actPlaceFlowers', { flowers: JSON.stringify(flowers) });
          });
        }
        // *** End of block
      }
    },

    getFlowerObject(color, x, y) {
      return { color: color, x: x, y: y };
    },
  });
});
