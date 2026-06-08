var $c01fd15b26b9b8db$exports = {};
$c01fd15b26b9b8db$exports = {
    "actions": `Handlinger`,
    "actionsAvailable": `Tilg\xe6ngelige handlinger.`,
    "clearSelection": `Ryd markering`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Ingen valgt`,
            other: ()=>`${formatter.number(args.count)} valgt`
        })}`,
    "selectedAll": `Alle valgt`
};


export {$c01fd15b26b9b8db$exports as default};
//# sourceMappingURL=da-DK.js.map
