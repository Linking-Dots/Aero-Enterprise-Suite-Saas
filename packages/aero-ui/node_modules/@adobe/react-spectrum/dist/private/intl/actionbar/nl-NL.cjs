module.exports = {
    "actions": `Acties`,
    "actionsAvailable": `Acties beschikbaar.`,
    "clearSelection": `Selectie wissen`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Niets geselecteerd`,
            other: ()=>`${formatter.number(args.count)} geselecteerd`
        })}`,
    "selectedAll": `Alles geselecteerd`
};


//# sourceMappingURL=nl-NL.cjs.map
