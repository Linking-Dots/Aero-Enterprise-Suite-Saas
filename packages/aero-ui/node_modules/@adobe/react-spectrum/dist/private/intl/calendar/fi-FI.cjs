module.exports = {
    "invalidSelection": (args, formatter)=>`${formatter.plural(args.selectedCount, {
            one: `Valittu p\xe4iv\xe4m\xe4\xe4r\xe4 ei ole`,
            other: `Valitut p\xe4iv\xe4m\xe4\xe4r\xe4t eiv\xe4t ole`
        })} saatavilla.`
};


//# sourceMappingURL=fi-FI.cjs.map
