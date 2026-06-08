module.exports = {
    "invalidSelection": (args, formatter)=>`${formatter.plural(args.selectedCount, {
            one: `La fecha seleccionada no est\xe1 disponible`,
            other: `Las fechas seleccionadas no est\xe1n disponibles`
        })}.`
};


//# sourceMappingURL=es-ES.cjs.map
