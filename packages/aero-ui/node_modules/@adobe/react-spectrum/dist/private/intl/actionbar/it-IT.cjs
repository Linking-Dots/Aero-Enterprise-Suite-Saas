module.exports = {
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


//# sourceMappingURL=it-IT.cjs.map
