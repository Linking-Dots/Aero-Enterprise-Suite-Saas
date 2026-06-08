module.exports = {
    "invalidSelection": (args, formatter)=>`${formatter.plural(args.selectedCount, {
            one: `Valt datum \xe4r inte tillg\xe4ngligt.`,
            other: `Valda datum \xe4r inte tillg\xe4ngliga.`
        })}`
};


//# sourceMappingURL=sv-SE.cjs.map
