import { InputDOMProps } from '@react-types/shared';
import React from 'react';
import { SpectrumBarSliderBase } from './SliderBase';
export interface SpectrumSliderProps extends SpectrumBarSliderBase<number>, InputDOMProps {
    /**
     * Whether a fill color is shown between the start of the slider and the current value.
     *
     * @see https://spectrum.adobe.com/page/slider/#Fill.
     */
    isFilled?: boolean;
    /**
     * The offset from which to start the fill.
     *
     * @see https://spectrum.adobe.com/page/slider/#Fill-start.
     */
    fillOffset?: number;
    /**
     * The background of the track, specified as the stops for a CSS background: `linear-gradient(to
     * right/left, ...trackGradient)`.
     *
     * @example
     *   trackGradient={['red', 'green']}
     *
     * @example
     *   trackGradient={['red 20%', 'green 40%']}
     *
     * @see https://spectrum.adobe.com/page/slider/#Gradient.
     */
    trackGradient?: string[];
}
/**
 * Sliders allow users to quickly select a value within a range. They should be used when the upper
 * and lower bounds to the range are invariable.
 */
export declare const Slider: React.ForwardRefExoticComponent<SpectrumSliderProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLDivElement, HTMLDivElement>>>;
