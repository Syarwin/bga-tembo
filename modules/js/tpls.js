define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
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
  <svg style="display:none" roles="img" xmlns="http://www.w3.org/2000/svg">
    <symbol id="rotate-clockwise-svg" viewBox="0 0 50.863451 119.83934">
      <path style="stroke-width:0.00490061" d="m 5.6758382,16.441679 c 5.7689738,2.03427 11.1946698,5.338719 15.8273158,9.992963 16.545113,16.5278 16.55915,43.330083 0.03135,59.875202 0,0 -4.548462,4.526763 -4.548462,4.526763 0,0 -0.007,-13.308545 -0.007,-13.308545 -0.0024,-4.68313 -3.787972,-8.464687 -8.4710998,-8.462236 -4.683123,0.0025 -8.464689,3.787977 -8.462235,8.471098 0,0 0.01766,33.734376 0.01766,33.734376 0,0 0,0 0,0 0,0 8.4e-5,0.1058 8.4e-5,0.1058 0.0025,4.68313 3.787982,8.46469 8.471098,8.46224 0,0 33.8666758,-0.0177 33.8666758,-0.0177 4.683116,-0.002 8.464683,-3.78796 8.462227,-8.47109 -0.0024,-4.68313 -3.787976,-8.46469 -8.471102,-8.46223 0,0 -13.546668,0.007 -13.546668,0.007 0,0 4.654249,-4.632654 4.654249,-4.632654 C 56.638843,75.099561 56.619198,37.58165 33.456041,14.442729 26.996822,7.990279 19.400845,3.337589 11.27664,0.48433903 6.8572832,-1.074391 2.0430912,1.256469 0.4843432,5.649369 c -1.558742,4.3929 0.772135,9.23356 5.165033,10.7923 0,0 0.02643,10e-6 0.02643,10e-6" />
    </symbol>
  </svg>
  <svg style="display:none" roles="img" xmlns="http://www.w3.org/2000/svg">
    <symbol id="rotate-cclockwise-svg" viewBox="0 0 51.189692 119.83862">
      <path style="stroke-width:0.07000434" d="m 45.48858,16.450478 c -5.775393,2.01596 -11.211548,5.303185 -15.858938,9.942713 -16.597453,16.475232 -16.696512,43.277336 -0.22128,59.874803 0,0 4.534081,4.541175 4.534081,4.541175 0,0 0.04918,-13.308462 0.04918,-13.308462 0.01731,-4.6831 3.814806,-8.452629 8.497909,-8.435325 4.683086,0.01731 8.452629,3.814809 8.435317,8.4979 0,0 -0.124665,33.734158 -0.124665,33.734158 0,0 0,0 0,0 0,0 -4.73e-4,0.10581 -4.73e-4,0.10581 -0.01731,4.68308 -3.814811,8.45261 -8.497892,8.43531 0,0 -33.8664466,-0.12517 -33.8664466,-0.12517 -4.683082,-0.0173 -8.45262698,-3.8148 -8.435312980909,-8.49789 C 0.01736942,106.5324 3.8148614,102.76287 8.4979554,102.78018 c 0,0 13.5465756,0.0501 13.5465756,0.0501 0,0 -4.639529,-4.647394 -4.639529,-4.647394 C -5.6603156,74.946404 -5.5216636,37.428746 17.714779,14.363418 24.194432,7.9314885 31.805132,3.3029185 39.938355,0.47544847 c 4.42427,-1.54468997 9.231049,0.80142003 10.775849,5.19925003 1.544801,4.3978195 -0.801421,9.2310495 -5.199241,10.7758495 0,0 -0.02642,-5e-5 -0.02642,-5e-5" />
    </symbol>
  </svg>
  `;
    },

    tplSavannaCard(card, tooltip = false) {
      let uid = (tooltip ? 'tooltip-' : '') + 'savanna-card-' + card.id;
      let typeId = card.type.split('_')[1];

      let rotation = '';
      if (card.rotation !== undefined) {
        rotation = ` data-rotation="${card.rotation || 0}"`;
      }

      return `<div id="${uid}" class='tembo-savanna-card' data-id='${card.id}' data-type='${typeId}' ${rotation}>
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
