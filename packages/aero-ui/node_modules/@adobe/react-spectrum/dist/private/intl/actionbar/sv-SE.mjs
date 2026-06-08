var $7abe5168b0869add$exports = {};
$7abe5168b0869add$exports = {
    "actions": `\xc5tg\xe4rder`,
    "actionsAvailable": `\xc5tg\xe4rder finns.`,
    "clearSelection": `Rensa markering`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Inga valda`,
            one: ()=>`${formatter.number(args.count)} vald`,
            other: ()=>`${formatter.number(args.count)} valda`
        })}`,
    "selectedAll": `Alla markerade`
};


export {$7abe5168b0869add$exports as default};
//# sourceMappingURL=sv-SE.mjs.map
