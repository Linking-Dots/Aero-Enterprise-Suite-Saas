var $afffe248d3abf937$exports = {};
$afffe248d3abf937$exports = {
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


export {$afffe248d3abf937$exports as default};
//# sourceMappingURL=sv-SE.js.map
