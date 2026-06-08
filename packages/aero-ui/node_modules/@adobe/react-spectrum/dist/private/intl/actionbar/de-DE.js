var $23891c5bee9f165a$exports = {};
$23891c5bee9f165a$exports = {
    "actions": `Aktionen`,
    "actionsAvailable": `Aktionen verf\xfcgbar.`,
    "clearSelection": `Auswahl l\xf6schen`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Nichts ausgew\xe4hlt`,
            one: ()=>`${formatter.number(args.count)} ausgew\xe4hlt`,
            other: ()=>`${formatter.number(args.count)} ausgew\xe4hlt`
        })}`,
    "selectedAll": `Alles ausgew\xe4hlt`
};


export {$23891c5bee9f165a$exports as default};
//# sourceMappingURL=de-DE.js.map
