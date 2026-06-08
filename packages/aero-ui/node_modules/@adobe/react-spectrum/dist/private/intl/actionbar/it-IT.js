var $3b48019321dc20b1$exports = {};
$3b48019321dc20b1$exports = {
    "actions": `Azioni`,
    "actionsAvailable": `Azioni disponibili.`,
    "clearSelection": `Annulla selezione`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Nessuno selezionato`,
            one: ()=>`${formatter.number(args.count)} selezionato`,
            other: ()=>`${formatter.number(args.count)} selezionati`
        })}`,
    "selectedAll": `Tutti selezionati`
};


export {$3b48019321dc20b1$exports as default};
//# sourceMappingURL=it-IT.js.map
