define(['dojo', 'dojo/_base/declare', 'ebg/counter'], (dojo, declare) => {
  const DIRECTIONS = [
    [-1, 0],
    [1, 0],
    [0, 1],
    [0, -1],
    [0, 0],
  ];

  // Everything ralted to playerboards
  return declare('tembo.board', null, {
    // getCell(cell, pId = null) {
    //   if (pId == null) pId = this.player_id;
    //   return $(`cell-${pId}-${cell.x}-${cell.y}`);
    // },
    // extractCellFromUId(uid) {
    //   let t = uid.split('-');
    //   return { x: parseInt(t[0]), y: parseInt(t[1]) };
    // },
  });
});
