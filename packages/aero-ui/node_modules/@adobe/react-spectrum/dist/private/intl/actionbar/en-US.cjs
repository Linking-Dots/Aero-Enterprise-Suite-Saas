module.exports = {
    "clearSelection": `Clear selection`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `None selected`,
            other: ()=>`${formatter.number(args.count)} selected`
        })}`,
    "selectedAll": `All selected`,
    "actions": `Actions`,
    "actionsAvailable": `Actions available.`
};


//# sourceMappingURL=en-US.cjs.map
