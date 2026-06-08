var $ee9b54db07e16669$exports = {};
$ee9b54db07e16669$exports = {
    "actions": `\u{5E4}\u{5E2}\u{5D5}\u{5DC}\u{5D5}\u{5EA}`,
    "actionsAvailable": `\u{5E4}\u{5E2}\u{5D5}\u{5DC}\u{5D5}\u{5EA} \u{5D6}\u{5DE}\u{5D9}\u{5E0}\u{5D5}\u{5EA}.`,
    "clearSelection": `\u{5E0}\u{5E7}\u{5D4} \u{5D1}\u{5D7}\u{5D9}\u{5E8}\u{5D4}`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `\u{5DC}\u{5D0} \u{5D1}\u{5D5}\u{5E6}\u{5E2}\u{5D4} \u{5D1}\u{5D7}\u{5D9}\u{5E8}\u{5D4}`,
            one: ()=>` ${formatter.number(args.count)} \u{5D1}\u{5D7}\u{5E8}`,
            other: ()=>`${formatter.number(args.count)} \u{5E0}\u{5D1}\u{5D7}\u{5E8}\u{5D5}`
        })}",`,
    "selectedAll": `\u{5DB}\u{5DC} \u{5D4}\u{5E4}\u{5E8}\u{5D9}\u{5D8}\u{5D9}\u{5DD} \u{5E9}\u{5E0}\u{5D1}\u{5D7}\u{5E8}\u{5D5}`
};


export {$ee9b54db07e16669$exports as default};
//# sourceMappingURL=he-IL.mjs.map
