var $0df674d81c460aa0$exports = {};
$0df674d81c460aa0$exports = {
    "actions": `A\xe7\xf5es`,
    "actionsAvailable": `A\xe7\xf5es dispon\xedveis.`,
    "clearSelection": `Limpar sele\xe7\xe3o`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Nenhum selecionado`,
            one: ()=>`${formatter.number(args.count)} selecionado`,
            other: ()=>`${formatter.number(args.count)} selecionados`
        })}`,
    "selectedAll": `Todos selecionados`
};


export {$0df674d81c460aa0$exports as default};
//# sourceMappingURL=pt-BR.mjs.map
