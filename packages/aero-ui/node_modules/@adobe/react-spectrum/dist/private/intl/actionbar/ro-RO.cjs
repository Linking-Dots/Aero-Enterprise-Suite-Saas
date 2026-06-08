module.exports = {
    "actions": `Ac\u{21B}iuni`,
    "actionsAvailable": `Ac\u{21B}iuni disponibile.`,
    "clearSelection": `Goli\u{21B}i selec\u{21B}ia`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Niciunul selectat`,
            one: ()=>` ${formatter.number(args.count)} selectat`,
            other: ()=>`${formatter.number(args.count)} selectate`
        })}`,
    "selectedAll": `Toate selectate`
};


//# sourceMappingURL=ro-RO.cjs.map
