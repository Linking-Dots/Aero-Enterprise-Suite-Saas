import { ColorVersion, DimensionValue, Direction, Responsive, StyleProps, ViewStyleProps } from '@react-types/shared';
import { CSSProperties, HTMLAttributes } from 'react';
type Breakpoint = 'base' | 'S' | 'M' | 'L' | string;
type StyleName = string | string[] | ((dir: Direction) => string);
type StyleHandler = (value: any, colorVersion?: number) => string | undefined;
export interface StyleHandlers {
    [key: string]: [StyleName, StyleHandler];
}
export declare const baseStyleProps: StyleHandlers;
export declare const viewStyleProps: StyleHandlers;
export declare function dimensionValue(value: DimensionValue): string | undefined;
export declare function responsiveDimensionValue(value: Responsive<DimensionValue>, matchedBreakpoints: Breakpoint[]): string | undefined;
export declare function convertStyleProps<C extends ColorVersion>(props: ViewStyleProps<C>, handlers: StyleHandlers, direction: Direction, matchedBreakpoints: Breakpoint[]): CSSProperties;
type StylePropsOptions = {
    matchedBreakpoints?: Breakpoint[];
};
export declare function useStyleProps<T extends StyleProps>(props: T, handlers?: StyleHandlers, options?: StylePropsOptions): {
    styleProps: HTMLAttributes<HTMLElement>;
};
export declare function passthroughStyle<T>(value: T): T;
export declare function getResponsiveProp<T>(prop: Responsive<T>, matchedBreakpoints: Breakpoint[]): T | undefined;
export {};
