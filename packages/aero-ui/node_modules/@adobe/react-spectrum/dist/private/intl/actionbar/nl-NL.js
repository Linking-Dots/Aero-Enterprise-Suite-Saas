var $1b19b031f3e9bf7c$exports = {};
$1b19b031f3e9bf7c$exports = {
    "actions": `Acties`,
    "actionsAvailable": `Acties beschikbaar.`,
    "clearSelection": `Selectie wissen`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Niets geselecteerd`,
            other: ()=>`${formatter.number(args.count)} geselecteerd`
        })}`,
    "selectedAll": `Alles geselecteerd`
};


export {$1b19b031f3e9bf7c$exports as default};
//# sourceMappingURL=nl-NL.js.map
