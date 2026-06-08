var $ea270e5b66fba273$exports = {};
$ea270e5b66fba273$exports = {
    "actions": `Toimingud`,
    "actionsAvailable": `Toimingud saadaval.`,
    "clearSelection": `Puhasta valik`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Pole valitud`,
            other: ()=>`${formatter.number(args.count)} valitud`
        })}`,
    "selectedAll": `K\xf5ik valitud`
};


export {$ea270e5b66fba273$exports as default};
//# sourceMappingURL=et-EE.mjs.map
