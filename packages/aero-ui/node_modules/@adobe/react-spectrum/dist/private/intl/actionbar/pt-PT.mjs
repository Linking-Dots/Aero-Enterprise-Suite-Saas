var $1b8eae297907b881$exports = {};
$1b8eae297907b881$exports = {
    "actions": `A\xe7\xf5es`,
    "actionsAvailable": `A\xe7\xf5es dispon\xedveis.`,
    "clearSelection": `Limpar sele\xe7\xe3o`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Nenhum selecionado`,
            one: ()=>`${formatter.number(args.count)} selecionado`,
            other: ()=>`${formatter.number(args.count)} selecionados`
        })}`,
    "selectedAll": `Tudo selecionado`
};


export {$1b8eae297907b881$exports as default};
//# sourceMappingURL=pt-PT.mjs.map
