var $17024395658ada6f$exports = {};
$17024395658ada6f$exports = {
    "clearSelection": `Clear selection`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `None selected`,
            other: ()=>`${formatter.number(args.count)} selected`
        })}`,
    "selectedAll": `All selected`,
    "actions": `Actions`,
    "actionsAvailable": `Actions available.`
};


export {$17024395658ada6f$exports as default};
//# sourceMappingURL=en-US.js.map
