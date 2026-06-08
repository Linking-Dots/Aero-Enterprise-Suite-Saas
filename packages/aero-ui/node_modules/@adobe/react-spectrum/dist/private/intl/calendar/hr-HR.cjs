module.exports = {
    "invalidSelection": (args, formatter)=>`${formatter.plural(args.selectedCount, {
            one: `Odabrani datum nije dostupan`,
            other: `Odabrani datumi nisu dostupni`
        })}.`
};


//# sourceMappingURL=hr-HR.cjs.map
