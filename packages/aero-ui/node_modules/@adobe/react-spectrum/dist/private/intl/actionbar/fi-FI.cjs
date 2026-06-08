module.exports = {
    "actions": `Toiminnot`,
    "actionsAvailable": `Toiminnot k\xe4ytett\xe4viss\xe4.`,
    "clearSelection": `Poista valinta`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Ei mit\xe4\xe4n valittu`,
            other: ()=>`${formatter.number(args.count)} valittu`
        })}`,
    "selectedAll": `Kaikki valittu`
};


//# sourceMappingURL=fi-FI.cjs.map
