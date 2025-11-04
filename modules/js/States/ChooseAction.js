define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('tembo.chooseAction', null, {
    constructor() {},

    onEnteringStateChooseActio(args) {
      if (this.isCurrentPlayerActive()) {
        Object.keys(args.options).forEach((key) => {
          const arg = args.options[key];
          this.addPrimaryActionButton(`btn${arg.option}`, _(arg.description),  () => {
            this.bgaPerformAction('actChooseAction', {choiceId: arg.option});
          });
        });
      }
    },
  });
});
