import { AriaProgressBarProps } from 'react-aria/useProgressBar';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes } from '@react-types/shared';
import React from 'react';
export interface ProgressBarProps extends Omit<AriaProgressBarProps, 'label'>, RenderProps<ProgressBarRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ProgressBar'
     */
    className?: ClassNameOrFunction<ProgressBarRenderProps>;
}
export interface ProgressBarRenderProps {
    /**
     * The value as a percentage between the minimum and maximum.
     */
    percentage: number | undefined;
    /**
     * A formatted version of the value.
     *
     * @selector [aria-valuetext]
     */
    valueText: string | undefined;
    /**
     * Whether the progress bar is indeterminate.
     *
     * @selector :not([aria-valuenow])
     */
    isIndeterminate: boolean;
}
export declare const ProgressBarContext: React.Context<ContextValue<ProgressBarProps, HTMLDivElement>>;
/**
 * Progress bars show either determinate or indeterminate progress of an operation
 * over time.
 */
export declare const ProgressBar: React.ForwardRefExoticComponent<ProgressBarProps & React.RefAttributes<HTMLDivElement>>;
