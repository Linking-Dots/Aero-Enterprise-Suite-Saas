var $05f7af59b0d2be6d$exports = {};
$05f7af59b0d2be6d$exports = {
    "clearSelection": `Clear selection`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `None selected`,
            other: ()=>`${formatter.number(args.count)} selected`
        })}`,
    "selectedAll": `All selected`,
    "actions": `Actions`,
    "actionsAvailable": `Actions available.`
};


export {$05f7af59b0d2be6d$exports as default};
//# sourceMappingURL=en-US.mjs.map
