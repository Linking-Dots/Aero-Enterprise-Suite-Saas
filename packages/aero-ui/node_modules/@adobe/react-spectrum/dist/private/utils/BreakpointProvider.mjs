import $dVSWG$react, {useState as $dVSWG$useState, useEffect as $dVSWG$useEffect, useContext as $dVSWG$useContext} from "react";
import {useIsSSR as $dVSWG$useIsSSR} from "react-aria/SSRProvider";



const $367536236d783ddf$var$Context = /*#__PURE__*/ (0, $dVSWG$react).createContext(null);
$367536236d783ddf$var$Context.displayName = 'BreakpointContext';
function $367536236d783ddf$export$8214320346cf5104(props) {
    let { children: children, matchedBreakpoints: matchedBreakpoints } = props;
    return /*#__PURE__*/ (0, $dVSWG$react).createElement($367536236d783ddf$var$Context.Provider, {
        value: {
            matchedBreakpoints: matchedBreakpoints
        }
    }, children);
}
function $367536236d783ddf$export$140ae7baa51cca23(breakpoints) {
    let entries = Object.entries(breakpoints).sort(([, valueA], [, valueB])=>valueB - valueA);
    let breakpointQueries = entries.map(([, value])=>`(min-width: ${value}px)`);
    let supportsMatchMedia = typeof window !== 'undefined' && typeof window.matchMedia === 'function';
    let getBreakpointHandler = ()=>{
        let matched = [];
        for(let i in breakpointQueries){
            let query = breakpointQueries[i];
            if (window.matchMedia(query).matches) matched.push(entries[i][0]);
        }
        matched.push('base');
        return matched;
    };
    let [breakpoint, setBreakpoint] = (0, $dVSWG$useState)(()=>supportsMatchMedia ? getBreakpointHandler() : [
            'base'
        ]);
    (0, $dVSWG$useEffect)(()=>{
        if (!supportsMatchMedia) return;
        let onResize = ()=>{
            const breakpointHandler = getBreakpointHandler();
            setBreakpoint((previousBreakpointHandler)=>{
                if (previousBreakpointHandler.length !== breakpointHandler.length || previousBreakpointHandler.some((breakpoint, idx)=>breakpoint !== breakpointHandler[idx])) return [
                    ...breakpointHandler
                ]; // Return a new array to force state change
                return previousBreakpointHandler;
            });
        };
        window.addEventListener('resize', onResize);
        return ()=>{
            window.removeEventListener('resize', onResize);
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [
        supportsMatchMedia
    ]);
    // If in SSR, the media query should never match. Once the page hydrates,
    // this will update and the real value will be returned.
    let isSSR = (0, $dVSWG$useIsSSR)();
    return isSSR ? [
        'base'
    ] : breakpoint;
}
function $367536236d783ddf$export$199d6754bdf4e1e3() {
    return (0, $dVSWG$useContext)($367536236d783ddf$var$Context);
}


export {$367536236d783ddf$export$8214320346cf5104 as BreakpointProvider, $367536236d783ddf$export$140ae7baa51cca23 as useMatchedBreakpoints, $367536236d783ddf$export$199d6754bdf4e1e3 as useBreakpoint};
//# sourceMappingURL=BreakpointProvider.mjs.map
