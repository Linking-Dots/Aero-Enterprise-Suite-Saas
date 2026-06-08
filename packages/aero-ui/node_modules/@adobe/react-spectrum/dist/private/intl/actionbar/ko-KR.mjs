var $c297b7dc2fc1b7ee$exports = {};
$c297b7dc2fc1b7ee$exports = {
    "actions": `\u{C561}\u{C158}`,
    "actionsAvailable": `\u{C0AC}\u{C6A9} \u{AC00}\u{B2A5}\u{D55C} \u{C561}\u{C158}`,
    "clearSelection": `\u{C120}\u{D0DD} \u{D56D}\u{BAA9} \u{C9C0}\u{C6B0}\u{AE30}`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `\u{C120}\u{D0DD}\u{B41C} \u{D56D}\u{BAA9} \u{C5C6}\u{C74C}`,
            other: ()=>`${formatter.number(args.count)}\u{AC1C} \u{C120}\u{D0DD}\u{B428}`
        })}`,
    "selectedAll": `\u{BAA8}\u{B450} \u{C120}\u{D0DD}\u{B428}`
};


export {$c297b7dc2fc1b7ee$exports as default};
//# sourceMappingURL=ko-KR.mjs.map
