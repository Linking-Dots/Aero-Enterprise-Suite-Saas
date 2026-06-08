import { AriaMeterProps } from 'react-aria/useMeter';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes } from '@react-types/shared';
import React from 'react';
export interface MeterProps extends Omit<AriaMeterProps, 'label'>, RenderProps<MeterRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Meter'
     */
    className?: ClassNameOrFunction<MeterRenderProps>;
}
export interface MeterRenderProps {
    /**
     * The value as a percentage between the minimum and maximum.
     */
    percentage: number;
    /**
     * A formatted version of the value.
     *
     * @selector [aria-valuetext]
     */
    valueText: string | undefined;
}
export declare const MeterContext: React.Context<ContextValue<MeterProps, HTMLDivElement>>;
/**
 * A meter represents a quantity within a known range, or a fractional value.
 */
export declare const Meter: (props: MeterProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
