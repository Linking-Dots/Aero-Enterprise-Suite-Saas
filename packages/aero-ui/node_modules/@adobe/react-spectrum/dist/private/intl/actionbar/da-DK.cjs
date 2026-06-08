module.exports = {
    "actions": `Handlinger`,
    "actionsAvailable": `Tilg\xe6ngelige handlinger.`,
    "clearSelection": `Ryd markering`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Ingen valgt`,
            other: ()=>`${formatter.number(args.count)} valgt`
        })}`,
    "selectedAll": `Alle valgt`
};


//# sourceMappingURL=da-DK.cjs.map
