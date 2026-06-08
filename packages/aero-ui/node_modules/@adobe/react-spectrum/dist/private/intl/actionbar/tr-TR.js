var $68d373186bb1f421$exports = {};
$68d373186bb1f421$exports = {
    "actions": `Eylemler`,
    "actionsAvailable": `Eylemler mevcut.`,
    "clearSelection": `Se\xe7imi temizle`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Hi\xe7biri se\xe7ilmedi`,
            other: ()=>`${formatter.number(args.count)} se\xe7ildi`
        })}`,
    "selectedAll": `T\xfcm\xfc se\xe7ildi`
};


export {$68d373186bb1f421$exports as default};
//# sourceMappingURL=tr-TR.js.map
