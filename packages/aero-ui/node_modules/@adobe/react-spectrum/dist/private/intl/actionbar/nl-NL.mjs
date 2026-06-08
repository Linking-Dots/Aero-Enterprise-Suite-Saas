var $425c77afcbb37476$exports = {};
$425c77afcbb37476$exports = {
    "actions": `Acties`,
    "actionsAvailable": `Acties beschikbaar.`,
    "clearSelection": `Selectie wissen`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Niets geselecteerd`,
            other: ()=>`${formatter.number(args.count)} geselecteerd`
        })}`,
    "selectedAll": `Alles geselecteerd`
};


export {$425c77afcbb37476$exports as default};
//# sourceMappingURL=nl-NL.mjs.map
