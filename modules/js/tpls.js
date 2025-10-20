define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const COLORS_FULL_TYPE = {
    b: 'flower-blue',
    y: 'flower-yellow',
    r: 'flower-red',
    w: 'flower-white',
    g: 'flower-grey',
    j: 'flower-joker',
  };

  const ZONES_SCORING = {
    2: [2, 3],
    3: [4, 6],
    4: [6, 9],
    5: [8, 12],
  };

  const ZONE_SHAPES = {
    // PINK_CIRCLE
    0: '2',
    1: '3I',
    2: '2',
    3: '2',
    // RED_CIRCLE
    4: '2',
    5: '3L',
    6: '3I',
    // BLUE_CIRCLE
    7: '4O',
    8: '2',
    9: '2',
    // WHITE_CIRCLE
    10: '4T',
    11: '2',
    12: '2',
    // PINK_SQUARE
    13: '2',
    14: '5S',
    15: '2',
    // RED_SQUARE
    16: '5L',
    17: '3L',
    // BLUE_SQUARE
    18: '4L',
    19: '4S',
    // WHITE_SQUARE
    20: '3I',
    21: '2',
    22: '3L',
  };

  return declare('tembo.htmltemplates', null, {
    centralAreaHtml() {
      return `
      <div id="tembo-main-container">
        <div id="flower-cards-container">
          <div id="flower-cards-container-sticky">
            <div id="flower-cards-container-resizable">
              <div id="flower-cards-holder"></div>
            </div>
          </div>
        </div>
      
        <div id="player-boards-container">
          <div id="player-boards-container-resizable">
            <div class="icon-lion"></div>
          </div>
        </div>
      
        <div id="ecosystem-cards-container">
          <div id="ecosystem-cards-container-sticky">
            <div id="ecosystem-cards-container-resizable">
              <div id="ecosystem-cards-holder"></div>
            </div>
          </div>
        </div>
      </div>


  <svg style="display:none" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker-question" role="img" xmlns="http://www.w3.org/2000/svg">
    <symbol id="help-marker-svg" viewBox="0 0 512 512"><g class="fa-group"><path class="fa-secondary" fill="white" d="M256 8C119 8 8 119.08 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 422a46 46 0 1 1 46-46 46.05 46.05 0 0 1-46 46zm40-131.33V300a12 12 0 0 1-12 12h-56a12 12 0 0 1-12-12v-4c0-41.06 31.13-57.47 54.65-70.66 20.17-11.31 32.54-19 32.54-34 0-19.82-25.27-33-45.7-33-27.19 0-39.44 13.14-57.3 35.79a12 12 0 0 1-16.67 2.13L148.82 170a12 12 0 0 1-2.71-16.26C173.4 113 208.16 90 262.66 90c56.34 0 116.53 44 116.53 102 0 77-83.19 78.21-83.19 106.67z" opacity="1"></path><path class="fa-primary" fill="currentColor" d="M256 338a46 46 0 1 0 46 46 46 46 0 0 0-46-46zm6.66-248c-54.5 0-89.26 23-116.55 63.76a12 12 0 0 0 2.71 16.24l34.7 26.31a12 12 0 0 0 16.67-2.13c17.86-22.65 30.11-35.79 57.3-35.79 20.43 0 45.7 13.14 45.7 33 0 15-12.37 22.66-32.54 34C247.13 238.53 216 254.94 216 296v4a12 12 0 0 0 12 12h56a12 12 0 0 0 12-12v-1.33c0-28.46 83.19-29.67 83.19-106.67 0-58-60.19-102-116.53-102z"></path></g>
    </symbol>
  </svg>`;
    },

    tplFlowerCard(card, tooltip = false) {
      let uid = (tooltip ? 'tooltip-' : '') + 'flower-card-' + card.id;
      let type = card.flowers.join('');

      return `<div id="${uid}" class='tembo-flower-card' data-id='${card.id}' data-type='${type}'>
          <div class='tembo-flower-card-wrapper'></div>
        </div>`;
    },

    tplEcosystemCard(card, tooltip = false) {
      let uid = (tooltip ? 'tooltip-' : '') + 'ecosystem-card-' + card.id;

      return `<div id="${uid}" class='tembo-ecosystem-card' data-id='${card.id}'>
          <div class='tembo-ecosystem-card-wrapper'></div>
        </div>`;
    },

    tplMeeple(meeple) {
      let type = meeple.type;
      if (COLORS_FULL_TYPE[type] !== undefined) {
        type = COLORS_FULL_TYPE[type];
      }

      return `<div class="tembo-meeple tembo-icon icon-${type}" id="meeple-${meeple.id}" data-id="${meeple.id}" data-type="${type}"></div>`;
    },

    tplInfoPanel(animals) {
      let animalsReserves = '';
      animals.forEach((type) => {
        animalsReserves += `<div class='animal-reserve-holder' data-n="0">
          <span id='animal-reserve-${type}-counter' class='animal-reserve-counter'>0</span>x
          <div id='animal-reserve-${type}' class='animal-reserve tembo-icon icon-${type}'></div>
        </div>`;
      });

      return `
   <div class='player-board' id="info-panel">
     <div class="info-panel-row" id="player_config">
        <div id="show-scores">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
            <g class="fa-group">
              <path class="fa-secondary" fill="currentColor" d="M0 192v272a48 48 0 0 0 48 48h352a48 48 0 0 0 48-48V192zm324.13 141.91a11.92 11.92 0 0 1-3.53 6.89L281 379.4l9.4 54.6a12 12 0 0 1-17.4 12.6l-49-25.8-48.9 25.8a12 12 0 0 1-17.4-12.6l9.4-54.6-39.6-38.6a12 12 0 0 1 6.6-20.5l54.7-8 24.5-49.6a12 12 0 0 1 21.5 0l24.5 49.6 54.7 8a12 12 0 0 1 10.13 13.61zM304 128h32a16 16 0 0 0 16-16V16a16 16 0 0 0-16-16h-32a16 16 0 0 0-16 16v96a16 16 0 0 0 16 16zm-192 0h32a16 16 0 0 0 16-16V16a16 16 0 0 0-16-16h-32a16 16 0 0 0-16 16v96a16 16 0 0 0 16 16z" opacity="0.4"></path>
              <path class="fa-primary" fill="currentColor" d="M314 320.3l-54.7-8-24.5-49.6a12 12 0 0 0-21.5 0l-24.5 49.6-54.7 8a12 12 0 0 0-6.6 20.5l39.6 38.6-9.4 54.6a12 12 0 0 0 17.4 12.6l48.9-25.8 49 25.8a12 12 0 0 0 17.4-12.6l-9.4-54.6 39.6-38.6a12 12 0 0 0-6.6-20.5zM400 64h-48v48a16 16 0 0 1-16 16h-32a16 16 0 0 1-16-16V64H160v48a16 16 0 0 1-16 16h-32a16 16 0 0 1-16-16V64H48a48 48 0 0 0-48 48v80h448v-80a48 48 0 0 0-48-48z"></path>
            </g>
          </svg>
        </div>

        <div id="turn-counter-wrapper">
          ${_('Turn')} <span id="turn-number">1</span> / <span id="max-turns">8</span>
        </div>

        <div id="help-mode-switch">
          <input type="checkbox" class="checkbox" id="help-mode-chk" />
          <label class="label" for="help-mode-chk">
            <div class="ball"></div>
          </label><svg aria-hidden="true" focusable="false" data-prefix="fad" data-icon="question-circle" class="svg-inline--fa fa-question-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><g class="fa-group"><path class="fa-secondary" fill="currentColor" d="M256 8C119 8 8 119.08 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 422a46 46 0 1 1 46-46 46.05 46.05 0 0 1-46 46zm40-131.33V300a12 12 0 0 1-12 12h-56a12 12 0 0 1-12-12v-4c0-41.06 31.13-57.47 54.65-70.66 20.17-11.31 32.54-19 32.54-34 0-19.82-25.27-33-45.7-33-27.19 0-39.44 13.14-57.3 35.79a12 12 0 0 1-16.67 2.13L148.82 170a12 12 0 0 1-2.71-16.26C173.4 113 208.16 90 262.66 90c56.34 0 116.53 44 116.53 102 0 77-83.19 78.21-83.19 106.67z" opacity="0.4"></path><path class="fa-primary" fill="currentColor" d="M256 338a46 46 0 1 0 46 46 46 46 0 0 0-46-46zm6.66-248c-54.5 0-89.26 23-116.55 63.76a12 12 0 0 0 2.71 16.24l34.7 26.31a12 12 0 0 0 16.67-2.13c17.86-22.65 30.11-35.79 57.3-35.79 20.43 0 45.7 13.14 45.7 33 0 15-12.37 22.66-32.54 34C247.13 238.53 216 254.94 216 296v4a12 12 0 0 0 12 12h56a12 12 0 0 0 12-12v-1.33c0-28.46 83.19-29.67 83.19-106.67 0-58-60.19-102-116.53-102z"></path></g></svg>
        </div>

        <div id="show-settings">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
            <g>
              <path class="fa-secondary" fill="currentColor" d="M638.41 387a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4L602 335a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6 12.36 12.36 0 0 0-15.1 5.4l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 44.9c-29.6-38.5 14.3-82.4 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79zm136.8-343.8a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4l8.2-14.3a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6A12.36 12.36 0 0 0 552 7.19l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 45c-29.6-38.5 14.3-82.5 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79z" opacity="0.4"></path>
              <path class="fa-primary" fill="currentColor" d="M420 303.79L386.31 287a173.78 173.78 0 0 0 0-63.5l33.7-16.8c10.1-5.9 14-18.2 10-29.1-8.9-24.2-25.9-46.4-42.1-65.8a23.93 23.93 0 0 0-30.3-5.3l-29.1 16.8a173.66 173.66 0 0 0-54.9-31.7V58a24 24 0 0 0-20-23.6 228.06 228.06 0 0 0-76 .1A23.82 23.82 0 0 0 158 58v33.7a171.78 171.78 0 0 0-54.9 31.7L74 106.59a23.91 23.91 0 0 0-30.3 5.3c-16.2 19.4-33.3 41.6-42.2 65.8a23.84 23.84 0 0 0 10.5 29l33.3 16.9a173.24 173.24 0 0 0 0 63.4L12 303.79a24.13 24.13 0 0 0-10.5 29.1c8.9 24.1 26 46.3 42.2 65.7a23.93 23.93 0 0 0 30.3 5.3l29.1-16.7a173.66 173.66 0 0 0 54.9 31.7v33.6a24 24 0 0 0 20 23.6 224.88 224.88 0 0 0 75.9 0 23.93 23.93 0 0 0 19.7-23.6v-33.6a171.78 171.78 0 0 0 54.9-31.7l29.1 16.8a23.91 23.91 0 0 0 30.3-5.3c16.2-19.4 33.7-41.6 42.6-65.8a24 24 0 0 0-10.5-29.1zm-151.3 4.3c-77 59.2-164.9-28.7-105.7-105.7 77-59.2 164.91 28.7 105.71 105.7z"></path>
            </g>
          </svg>
        </div>
     </div>

      <div class="info-panel-row" id="animals-reserves">
        ${animalsReserves}
      </div>
   </div>
   `;
    },

    tplFlowerIcon(type, isButton = false) {
      const buttonClass = isButton ? ' button-icon' : '';
      return `<div class="tembo-icon ${type}${buttonClass} status-bar-icon"></div>`;
    },

    tplPlayerBoard(player) {
      let boards = this.gamedatas.board.ids;
      let grid = '';

      // Quadrant
      for (let i = 0; i < 4; i++) {
        let board = boards[i];
        grid += `<div class='board-quadrant' data-quadrant='${i}' data-board='${board[0]}' data-orientation='${board[1]}'></div>`;
      }

      // Zone indicators
      Object.entries(this.gamedatas.board.zones).forEach(([zoneId, zone]) => {
        // Compute where to place the shape
        let minX = 6,
          minY = 6,
          maxY = 0,
          maxX = 0;
        zone.cells.forEach((cell) => {
          minX = Math.min(minX, cell.x);
          minY = Math.min(minY, cell.y);
          maxX = Math.max(maxX, cell.x);
          maxY = Math.max(maxY, cell.y);
        });
        let midY = (minY + maxY) / 2;
        let column = '';
        if ((minY + maxY) % 2 == 1) {
          column = `${midY + 0.5} / span 2`;
        } else {
          column = `${midY + 1} / span 1`;
        }

        // Find info about that zone
        let scoring = ZONES_SCORING[zone.cells.length];
        let shape = 'zone-' + ZONE_SHAPES[zoneId];

        grid += `<div class='zone-infos-wrapper zone-info-${zoneId}' style='grid-row-start:${minX + 1}; grid-column:${column}'>
          <div class='zone-infos'>
            <div class='zone-size-scoring'>
              <span id='zone-${player.id}-${zoneId}'>${scoring[0]}</span>${this.formatIcon(shape, null, false)}
            </div>
            <div id='zone-animal-${player.id}-${zoneId}' class='zone-animal-scoring'>${scoring[1]}</div>
          </div>
        </div>`;
      });

      // Cells
      for (let x = 0; x < 6; x++) {
        for (let y = 0; y < 6; y++) {
          grid += `<div class='board-cell' id='cell-${player.id}-${x}-${y}' style='grid-row-start:${x + 1}; grid-column-start:${y + 1}'></div>`;
        }
      }

      return `<div class='tembo-player-board-resizable' id='player-board-resizable-${player.id}'>
            <div class='tembo-player-board' id='player-board-${player.id}'>
                <div class='tembo-board-player-name' style="color:#${player.color}">
                    ${player.name}
                </div>
                <div class='tembo-board-grid'>
                    ${grid}
                </div>
            </div>
          </div>`;
    },

    tplPlayerPanel(player) {
      return `<div class='player-info'>
        <div class="tembo-pangolin-holder" id="pangolin-${player.id}"></div>
      </div>`;
    },

    tplScoreModal() {
      let content = `<table id='scoresheet'>
        <tr id="score-row-name"><th>${this.formatIcon('player')}</th></tr>
        <tr id="score-row-trees"><th>${this.formatIcon('tree')}</th></tr>
        <tr id="score-row-animals"><th>${this.formatIcon('paw')}</th></tr>
        <tr id="score-row-completedAreas"><th>${this.formatIcon('ok')}</th></tr>
        <tr id="score-row-unfinishedAndMixed"><th>${this.formatIcon('nok')}</th></tr>`;

      if (this.gamedatas.ecosystemsTexts) {
        Object.keys(this.gamedatas.ecosystemsTexts).forEach((id) => {
          content += `<tr id='score-row-ecosystem-${id}' class='score-row-ecosystem'>
            <th>
              <div class='tembo-ecosystem-card' data-id='${id}'>
                <div class='tembo-ecosystem-card-wrapper'></div>
              </div>
            </th>
          </tr>`;
        });
      }

      content += `<tr id="score-row-overall"><th></th></tr>
      </table>`;
      return content;
    },

    tplEndGameText(starsAmount, text) {
      let stars = new Array(starsAmount).fill(this.tplFlowerIcon('icon-flower-yellow'));
      for (let i = 0; i < 5 - starsAmount; i++) {
        stars.push(this.tplFlowerIcon('icon-flower-empty'));
      }
      return `
<div id="end-game-text-block">
  <div id="end-game-text">${_(text)}</div>
  <div id="end-game-stars">${stars.join('')}</div>
</div>`;
    },
  });
});
