var $56fa0e15ee52159f$exports = {};
$56fa0e15ee52159f$exports = {
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


export {$56fa0e15ee52159f$exports as default};
//# sourceMappingURL=it-IT.mjs.map
