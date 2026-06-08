module.exports = {
    "actions": `\u{64CD}\u{4F5C}`,
    "actionsAvailable": `\u{6709}\u{53EF}\u{7528}\u{64CD}\u{4F5C}\u{3002}`,
    "clearSelection": `\u{6E05}\u{9664}\u{9009}\u{62E9}`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `\u{65E0}\u{9009}\u{62E9}`,
            other: ()=>`\u{5DF2}\u{9009}\u{62E9} ${formatter.number(args.count)} \u{4E2A}`
        })}`,
    "selectedAll": `\u{5168}\u{9009}`
};


//# sourceMappingURL=zh-CN.cjs.map
