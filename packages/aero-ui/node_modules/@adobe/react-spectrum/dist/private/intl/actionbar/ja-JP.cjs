module.exports = {
    "actions": `\u{30A2}\u{30AF}\u{30B7}\u{30E7}\u{30F3}`,
    "actionsAvailable": `\u{30A2}\u{30AF}\u{30B7}\u{30E7}\u{30F3}\u{3092}\u{5229}\u{7528}\u{3067}\u{304D}\u{307E}\u{3059}\u{3002}`,
    "clearSelection": `\u{9078}\u{629E}\u{3092}\u{30AF}\u{30EA}\u{30A2}`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `\u{9078}\u{629E}\u{3055}\u{308C}\u{3066}\u{3044}\u{307E}\u{305B}\u{3093}`,
            other: ()=>`${formatter.number(args.count)} \u{500B}\u{3092}\u{9078}\u{629E}\u{3057}\u{307E}\u{3057}\u{305F}`
        })}`,
    "selectedAll": `\u{3059}\u{3079}\u{3066}\u{3092}\u{9078}\u{629E}`
};


//# sourceMappingURL=ja-JP.cjs.map
