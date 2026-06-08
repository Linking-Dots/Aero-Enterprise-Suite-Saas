var $5fe6956108b28750$exports = {};
$5fe6956108b28750$exports = {
    "actions": `\u{52D5}\u{4F5C}`,
    "actionsAvailable": `\u{53EF}\u{57F7}\u{884C}\u{7684}\u{52D5}\u{4F5C}\u{3002}`,
    "clearSelection": `\u{6E05}\u{9664}\u{9078}\u{53D6}\u{9805}\u{76EE}`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `\u{672A}\u{9078}\u{53D6}\u{4EFB}\u{4F55}\u{9805}\u{76EE}`,
            other: ()=>`\u{5DF2}\u{9078}\u{53D6} ${formatter.number(args.count)} \u{500B}`
        })}`,
    "selectedAll": `\u{5DF2}\u{9078}\u{53D6}\u{6240}\u{6709}\u{9805}\u{76EE}`
};


export {$5fe6956108b28750$exports as default};
//# sourceMappingURL=zh-TW.js.map
