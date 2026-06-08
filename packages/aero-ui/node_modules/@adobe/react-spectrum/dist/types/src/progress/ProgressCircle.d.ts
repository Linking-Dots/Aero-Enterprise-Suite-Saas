import { AriaLabelingProps, DOMProps, StyleProps } from '@react-types/shared';
import React from 'react';
export interface ProgressCircleProps {
    /**
     * The current value (controlled).
     *
     * @default 0
     */
    value?: number;
    /**
     * The smallest value allowed for the input.
     *
     * @default 0
     */
    minValue?: number;
    /**
     * The largest value allowed for the input.
     *
     * @default 100
     */
    maxValue?: number;
    /**
     * Whether presentation is indeterminate when progress isn't known.
     */
    isIndeterminate?: boolean;
}
export interface AriaProgressCircleProps extends ProgressCircleProps, DOMProps, AriaLabelingProps {
}
export interface SpectrumProgressCircleProps extends AriaProgressCircleProps, StyleProps {
    /**
     * What the ProgressCircle's diameter should be.
     *
     * @default 'M'
     */
    size?: 'S' | 'M' | 'L';
    /** The static color style to apply. Useful when the button appears over a color background. */
    staticColor?: 'white' | 'black';
    /**
     * The [visual style](https://spectrum.adobe.com/page/progress-circle/#Over-background-variant) of
     * the ProgressCircle.
     *
     * @deprecated - use staticColor instead.
     */
    variant?: 'overBackground';
}
/**
 * ProgressCircles show the progression of a system operation such as downloading, uploading, or
 * processing, in a visual way. They can represent determinate or indeterminate progress.
 */
export declare const ProgressCircle: React.ForwardRefExoticComponent<SpectrumProgressCircleProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
