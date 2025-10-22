define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const LOCATION_TABLE = 'table';

  return declare('tembo.common', null, {
    constructor() {},

    extractId(element, prefix) {
      const unparsed = element.getAttribute('id').replace(`${prefix}-`, '');
      return isNaN(parseInt(unparsed)) ? unparsed : parseInt(unparsed);
    },
  });
});
