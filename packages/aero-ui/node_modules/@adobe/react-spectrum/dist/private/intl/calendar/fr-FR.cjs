module.exports = {
    "invalidSelection": (args, formatter)=>`${formatter.plural(args.selectedCount, {
            one: `La date s\xe9lectionn\xe9e est indisponible`,
            other: `Les dates s\xe9lectionn\xe9es sont indisponibles`
        })}.`
};


//# sourceMappingURL=fr-FR.cjs.map
