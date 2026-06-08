var $8f77c2c065c0d6f7$exports = {};
$8f77c2c065c0d6f7$exports = {
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


export {$8f77c2c065c0d6f7$exports as default};
//# sourceMappingURL=de-DE.mjs.map
