var $61c477f3ae2770ea$exports = {};
$61c477f3ae2770ea$exports = {
    "actions": `\u{414}\u{435}\u{439}\u{441}\u{442}\u{432}\u{438}\u{44F}`,
    "actionsAvailable": `\u{41D}\u{430}\u{43B}\u{438}\u{447}\u{43D}\u{438} \u{434}\u{435}\u{439}\u{441}\u{442}\u{432}\u{438}\u{44F}.`,
    "clearSelection": `\u{418}\u{437}\u{447}\u{438}\u{441}\u{442}\u{432}\u{430}\u{43D}\u{435} \u{43D}\u{430} \u{438}\u{437}\u{431}\u{43E}\u{440}\u{430}`,
    "selected": (args, formatter)=>`${formatter.plural(args.count, {
            "=0": `\u{41D}\u{44F}\u{43C}\u{430} \u{438}\u{437}\u{431}\u{440}\u{430}\u{43D}\u{438}`,
            one: ()=>`${formatter.number(args.count)} \u{438}\u{437}\u{431}\u{440}\u{430}\u{43D}`,
            other: ()=>`${formatter.number(args.count)} \u{438}\u{437}\u{431}\u{440}\u{430}\u{43D}\u{438}`
        })}`,
    "selectedAll": `\u{412}\u{441}\u{438}\u{447}\u{43A}\u{438} \u{438}\u{437}\u{431}\u{440}\u{430}\u{43D}\u{438}`
};


export {$61c477f3ae2770ea$exports as default};
//# sourceMappingURL=bg-BG.js.map
