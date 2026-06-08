import { AriaProgressBarBaseProps, ProgressBarProps } from 'react-aria/useProgressBar';
import { LabelPosition, StyleProps } from '@react-types/shared';
import React, { HTMLAttributes } from 'react';
export interface SpectrumProgressBarBaseProps extends AriaProgressBarBaseProps, StyleProps {
    /**
     * How thick the bar should be.
     *
     * @default 'L'
     */
    size?: 'S' | 'L';
    /**
     * The label's overall position relative to the element it is labeling.
     *
     * @default 'top'
     */
    labelPosition?: LabelPosition;
    /**
     * Whether the value's label is displayed. True by default if there's a label, false by default if
     * not.
     */
    showValueLabel?: boolean;
}
export interface SpectrumProgressBarProps extends SpectrumProgressBarBaseProps, ProgressBarProps {
    /** The static color style to apply. Useful when the button appears over a color background. */
    staticColor?: 'white' | 'black';
    /**
     * The [visual style](https://spectrum.adobe.com/page/progress-bar/#Over-background-variant) of
     * the ProgressBar.
     *
     * @deprecated - use staticColor instead.
     */
    variant?: 'overBackground';
}
interface ProgressBarBaseProps extends SpectrumProgressBarBaseProps, ProgressBarProps {
    barClassName?: string;
    barProps?: HTMLAttributes<HTMLDivElement>;
    labelProps?: HTMLAttributes<HTMLLabelElement>;
}
export declare const ProgressBarBase: React.ForwardRefExoticComponent<ProgressBarBaseProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
export {};
