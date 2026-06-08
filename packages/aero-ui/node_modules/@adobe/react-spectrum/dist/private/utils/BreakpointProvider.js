import $jR4MN$react, {useState as $jR4MN$useState, useEffect as $jR4MN$useEffect, useContext as $jR4MN$useContext} from "react";
import {useIsSSR as $jR4MN$useIsSSR} from "react-aria/SSRProvider";



const $cf1a1f4b586658ed$var$Context = /*#__PURE__*/ (0, $jR4MN$react).createContext(null);
$cf1a1f4b586658ed$var$Context.displayName = 'BreakpointContext';
function $cf1a1f4b586658ed$export$8214320346cf5104(props) {
    let { children: children, matchedBreakpoints: matchedBreakpoints } = props;
    return /*#__PURE__*/ (0, $jR4MN$react).createElement($cf1a1f4b586658ed$var$Context.Provider, {
        value: {
            matchedBreakpoints: matchedBreakpoints
        }
    }, children);
}
function $cf1a1f4b586658ed$export$140ae7baa51cca23(breakpoints) {
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
    let [breakpoint, setBreakpoint] = (0, $jR4MN$useState)(()=>supportsMatchMedia ? getBreakpointHandler() : [
            'base'
        ]);
    (0, $jR4MN$useEffect)(()=>{
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
    let isSSR = (0, $jR4MN$useIsSSR)();
    return isSSR ? [
        'base'
    ] : breakpoint;
}
function $cf1a1f4b586658ed$export$199d6754bdf4e1e3() {
    return (0, $jR4MN$useContext)($cf1a1f4b586658ed$var$Context);
}


export {$cf1a1f4b586658ed$export$8214320346cf5104 as BreakpointProvider, $cf1a1f4b586658ed$export$140ae7baa51cca23 as useMatchedBreakpoints, $cf1a1f4b586658ed$export$199d6754bdf4e1e3 as useBreakpoint};
//# sourceMappingURL=BreakpointProvider.js.map
