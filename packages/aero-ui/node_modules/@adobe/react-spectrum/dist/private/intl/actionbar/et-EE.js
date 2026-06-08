var $8821249e5e36c89f$exports = {};
$8821249e5e36c89f$exports = {
    "actions": `Toimingud`,
    "actionsAvailable": `Toimingud saadaval.`,
    "clearSelection": `Puhasta valik`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Pole valitud`,
            other: ()=>`${formatter.number(args.count)} valitud`
        })}`,
    "selectedAll": `K\xf5ik valitud`
};


export {$8821249e5e36c89f$exports as default};
//# sourceMappingURL=et-EE.js.map
