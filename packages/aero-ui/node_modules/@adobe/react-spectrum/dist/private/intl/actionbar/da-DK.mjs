var $5d6fb5b1c73b0805$exports = {};
$5d6fb5b1c73b0805$exports = {
    "actions": `Handlinger`,
    "actionsAvailable": `Tilg\xe6ngelige handlinger.`,
    "clearSelection": `Ryd markering`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Ingen valgt`,
            other: ()=>`${formatter.number(args.count)} valgt`
        })}`,
    "selectedAll": `Alle valgt`
};


export {$5d6fb5b1c73b0805$exports as default};
//# sourceMappingURL=da-DK.mjs.map
