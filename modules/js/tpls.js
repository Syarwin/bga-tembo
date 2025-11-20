define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const SHAPE_DIAG_DOWN = 0;
  const SHAPE_DIAG_UP = 1;
  const SHAPE_DASH_VERT = 2;
  const SHAPE_DASH_HOR = 3;
  const SHAPE_LONG_DASH_VERT = 4;
  const SHAPE_LONG_DASH_HOR = 5;
  const SHAPE_L = 6;
  const SHAPES_CELLS = {
    0: [
      [0, 0],
      [1, 1],
    ],
    1: [
      [0, 0],
      [1, -1],
    ],
    2: [
      [0, 0],
      [0, 1],
    ],
    3: [
      [0, 0],
      [1, 0],
    ],
    4: [
      [0, 0],
      [0, -1],
      [0, 1],
    ],
    5: [
      [0, 0],
      [-1, 0],
      [1, 0],
    ],
    6: [
      [0, 0],
      [-1, 0],
      [0, -1],
    ],
  };

  return declare('tembo.htmltemplates', null, {
    centralAreaHtml() {
      return `
      <div id="tembo-main-container">
        <div id="savanna-cards-container">
          <div id="savanna-cards-container-sticky">
            ${this.tplInfoPanel()}
            <div id="savanna-cards-container-resizable">
              <div id="savanna-cards-holder"></div>
            </div>
          </div>
        </div>
      
        <div id="board-container">
          <div id="tembo-board-resizable">
            <div id="tembo-board"></div>
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
  </svg>
  <svg style="display:none" roles="img" xmlns="http://www.w3.org/2000/svg"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) -->
    <symbol id="crosshairs-svg" viewBox="0 0 512 512">
      <path d="M500 224h-30.364C455.724 130.325 381.675 56.276 288 42.364V12c0-6.627-5.373-12-12-12h-40c-6.627 0-12 5.373-12 12v30.364C130.325 56.276 56.276 130.325 42.364 224H12c-6.627 0-12 5.373-12 12v40c0 6.627 5.373 12 12 12h30.364C56.276 381.675 130.325 455.724 224 469.636V500c0 6.627 5.373 12 12 12h40c6.627 0 12-5.373 12-12v-30.364C381.675 455.724 455.724 381.675 469.636 288H500c6.627 0 12-5.373 12-12v-40c0-6.627-5.373-12-12-12zM288 404.634V364c0-6.627-5.373-12-12-12h-40c-6.627 0-12 5.373-12 12v40.634C165.826 392.232 119.783 346.243 107.366 288H148c6.627 0 12-5.373 12-12v-40c0-6.627-5.373-12-12-12h-40.634C119.768 165.826 165.757 119.783 224 107.366V148c0 6.627 5.373 12 12 12h40c6.627 0 12-5.373 12-12v-40.634C346.174 119.768 392.217 165.757 404.634 224H364c-6.627 0-12 5.373-12 12v40c0 6.627 5.373 12 12 12h40.634C392.232 346.174 346.243 392.217 288 404.634zM288 256c0 17.673-14.327 32-32 32s-32-14.327-32-32c0-17.673 14.327-32 32-32s32 14.327 32 32z"/>
    </symbol>
  </svg>
  <svg style="display:none" roles="img" xmlns="http://www.w3.org/2000/svg">
    <symbol id="rotate-clockwise-svg" viewBox="0 0 80.682553 81.568624">
      <path style="stroke-width:0.00490061" d="M 3.1804694,9.6044667 C 5.0207714,10.634279 19.17348,24.001149 26.106796,31.895341 c 7.23688,8.239827 14.247557,17.681346 18.187531,23.104592 0,0 9.975814,12.940617 9.975814,12.940617 0,0 -19.459346,-3.945809 -19.459346,-3.945809 -4.589725,-0.930668 -8.211935,-1.28779 -9.11974,3.306516 -0.907796,4.59429 0.335949,6.636994 4.930239,7.544788 0,0 33.094524,6.539245 33.094524,6.539245 0,0 0,0 0,0 0,0 0.103817,0.02047 0.103817,0.02047 4.594283,0.907795 9.039478,-2.070565 9.947281,-6.664851 0,0 6.564866,-33.224354 6.564866,-33.224354 0.907804,-4.594285 0.252185,-6.589177 -4.342108,-7.496968 -4.594304,-0.907798 -6.684711,1.478114 -7.592507,6.072412 0,0 -4.315645,21.673594 -4.315645,21.673594 0,0 -9.2973,-12.869936 -9.2973,-12.869936 C 34.347638,22.781248 30.628419,19.399822 17.268412,7.2581223 11.503981,2.0193523 7.3982764,-3.1537894 1.1919334,2.3720027 c -2.618578,3.2729362 -0.45295698,5.1242905 1.988536,7.232464" />
    </symbol>
  </svg>
  <svg style="display:none" roles="img" xmlns="http://www.w3.org/2000/svg">
    <symbol id="rotate-cclockwise-svg" viewBox="0 0 80.682553 81.568616">
      <path style="stroke-width:0.07000434" d="M 77.502085,9.6044667 C 75.661783,10.634279 61.509074,24.001149 54.575758,31.895341 47.338878,40.135168 40.328201,49.576687 36.388227,54.999933 L 26.412413,67.94055 45.871759,63.994741 c 4.589725,-0.930668 8.211935,-1.28779 9.11974,3.306516 0.907796,4.59429 -0.335949,6.636994 -4.930239,7.544788 L 16.966736,81.38529 v 0 l -0.103817,0.02047 C 12.268636,82.313555 7.8234411,79.335195 6.9156382,74.740909 L 0.35077193,41.516555 c -0.90780373,-4.594285 -0.252185,-6.589177 4.34210897,-7.496968 4.594304,-0.907798 6.6847101,1.478114 7.5925061,6.072412 l 4.315645,21.673594 9.2973,-12.869936 C 46.334916,22.781248 50.054135,19.399822 63.414142,7.2581223 c 5.764431,-5.23877 9.870136,-10.4119117 16.076479,-4.8861196 2.618578,3.2729362 0.452957,5.1242905 -1.988536,7.232464" />
    </symbol>
  </svg>
  `;
    },

    tplSavannaCard(card, tooltip = false) {
      let uid = (tooltip ? 'tooltip-' : '') + 'savanna-card-' + card.id;

      let rotation = '';
      if (card.rotation !== undefined) {
        rotation = ` data-rotation="${card.rotation || 0}"`;
      }

      return `<div id="${uid}" class='tembo-savanna-card' data-id='${card.id}' ${rotation}>
          <div class='tembo-savanna-card-wrapper'></div>
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
      return `<div class="tembo-meeple meeple-${type}" id="meeple-${meeple.id}" data-id="${meeple.id}" data-type="${type}"></div>`;
    },

    tplInfoPanel() {
      return `
   <div class='player-board' id="info-panel">
     <div class="info-panel-row" id="player_config">
        <div id="energy-counter-holder">
          <span id="energy-counter"></span>
          <span class="counter-separator">/</span>
          <span class="counter-max">12</span>
          <div class="tembo-icon icon-energy"></div>
        </div>

        <div id="support-counter-holder">
          <span id="support-counter"></span>
          <div class="tembo-icon icon-support-token"></div>
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

      <div class="info-panel-row" id="trees-holder"></div>

      <div class="info-panel-row" id="landmarks-reserve"></div>
    </div>
   `;
    },

    tplSavannaIcon(type, isButton = false) {
      const buttonClass = isButton ? ' button-icon' : '';
      return `<div class="tembo-icon ${type}${buttonClass} status-bar-icon"></div>`;
    },

    tplPattern(shape, id = '') {
      let uid = id == '' ? '' : `id='${id}'`;

      const cells = SHAPES_CELLS[shape];
      let minX = 0,
        maxX = 0,
        minY = 0,
        maxY = 0;
      cells.forEach((cell) => {
        minX = Math.min(minX, cell[0]);
        maxX = Math.max(maxX, cell[0]);
        minY = Math.min(minY, cell[1]);
        maxY = Math.max(maxY, cell[1]);
      });
      const w = maxX - minX + 1,
        h = maxY - minY + 1;

      let tplCells = '';
      cells.forEach((cell) => {
        let crosshair =
          cell[0] == 0 && cell[1] == 0 ? '<div class="pattern-crosshairs"><svg><use href="#crosshairs-svg" /></svg></div>' : '';
        tplCells += `<div class='pattern-cell' data-col='${cell[0] - minX + 1}' data-row='${cell[1] - minY + 1}'>${crosshair}</div>`;
      });
      return `<div ${uid} class='tembo-pattern' data-shape='${shape}' data-w='${w}' data-h='${h}' data-offsetX='${minX}' data-offsetY='${minY}'>${tplCells}</div>`;
    },

    tplPlayerPanel(player) {
      return `<div class='player-info'>
        <div class='elephant-reserve-holder' data-n="0">
          <span id='elephant-reserve-${player.id}-counter' class='elephant-reserve-counter'>0</span>x
          <div id='elephant-reserve-${player.id}' class='elephant-reserve tembo-meeple meeple-elephant-1'></div>
        </div>

        <div class='tired-elephant-reserve-holder' data-n="0">
          <i class="fa fa-hourglass"></i>
          <span id='tired-elephant-reserve-${player.id}-counter' class='elephant-reserve-counter'>0</span>x
          <div id='tired-elephant-reserve-${player.id}' class='elephant-reserve tembo-meeple meeple-elephant-1'></div>
        </div>
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
  });
});
