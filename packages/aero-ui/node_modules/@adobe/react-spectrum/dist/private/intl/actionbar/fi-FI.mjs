var $a9b13cc42bd44013$exports = {};
$a9b13cc42bd44013$exports = {
    "actions": `Toiminnot`,
    "actionsAvailable": `Toiminnot k\xe4ytett\xe4viss\xe4.`,
    "clearSelection": `Poista valinta`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `Ei mit\xe4\xe4n valittu`,
            other: ()=>`${formatter.number(args.count)} valittu`
        })}`,
    "selectedAll": `Kaikki valittu`
};


export {$a9b13cc42bd44013$exports as default};
//# sourceMappingURL=fi-FI.mjs.map
