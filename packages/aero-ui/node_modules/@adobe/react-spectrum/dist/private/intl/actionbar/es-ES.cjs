module.exports = {
    "actions": `Acciones`,
    "actionsAvailable": `Acciones disponibles.`,
    "clearSelection": `Borrar selecci\xf3n`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Nada seleccionado`,
            one: ()=>`${formatter.number(args.count)} seleccionado`,
            other: ()=>`${formatter.number(args.count)} seleccionados`
        })}`,
    "selectedAll": `Todo seleccionado`
};


//# sourceMappingURL=es-ES.cjs.map
