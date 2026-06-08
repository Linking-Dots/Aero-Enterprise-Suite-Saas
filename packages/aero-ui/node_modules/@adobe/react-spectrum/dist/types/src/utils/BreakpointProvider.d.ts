import { ReactNode } from 'react';
interface Breakpoints {
    S?: number;
    M?: number;
    L?: number;
    [custom: string]: number | undefined;
}
interface BreakpointContext {
    matchedBreakpoints: string[];
}
interface BreakpointProviderProps {
    children?: ReactNode;
    matchedBreakpoints: string[];
}
export declare function BreakpointProvider(props: BreakpointProviderProps): ReactNode;
export declare function useMatchedBreakpoints(breakpoints: Breakpoints): string[];
export declare function useBreakpoint(): BreakpointContext | null;
export {};
