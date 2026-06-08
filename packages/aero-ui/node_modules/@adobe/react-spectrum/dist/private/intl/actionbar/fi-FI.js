var $6c00e8e279018240$exports = {};
$6c00e8e279018240$exports = {
    "actions": `Toiminnot`,
    "actionsAvailable": `Toiminnot k\xe4ytett\xe4viss\xe4.`,
    "clearSelection": `Poista valinta`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Ei mit\xe4\xe4n valittu`,
            other: ()=>`${formatter.number(args.count)} valittu`
        })}`,
    "selectedAll": `Kaikki valittu`
};


export {$6c00e8e279018240$exports as default};
//# sourceMappingURL=fi-FI.js.map
