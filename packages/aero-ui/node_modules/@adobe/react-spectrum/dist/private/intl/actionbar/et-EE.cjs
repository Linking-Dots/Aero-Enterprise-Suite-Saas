module.exports = {
    "actions": `Toimingud`,
    "actionsAvailable": `Toimingud saadaval.`,
    "clearSelection": `Puhasta valik`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Pole valitud`,
            other: ()=>`${formatter.number(args.count)} valitud`
        })}`,
    "selectedAll": `K\xf5ik valitud`
};


//# sourceMappingURL=et-EE.cjs.map
