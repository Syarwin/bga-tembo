define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('tembo.lexemes', null, {
    getDeckLexeme() {
      return _('Deck');
    },

    getDiscardLexeme() {
      return _('Discard');
    },
  });
});
