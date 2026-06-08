var $d4d2049854e57a69$exports = {};
$d4d2049854e57a69$exports = {
    "actions": `Actions`,
    "actionsAvailable": `Actions disponibles.`,
    "clearSelection": `Supprimer la s\xe9lection`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Aucun \xe9l\xe9ment s\xe9lectionn\xe9`,
            one: ()=>`${formatter.number(args.count)} s\xe9lectionn\xe9`,
            other: ()=>`${formatter.number(args.count)} s\xe9lectionn\xe9s`
        })}`,
    "selectedAll": `Toute la s\xe9lection`
};


export {$d4d2049854e57a69$exports as default};
//# sourceMappingURL=fr-FR.js.map
