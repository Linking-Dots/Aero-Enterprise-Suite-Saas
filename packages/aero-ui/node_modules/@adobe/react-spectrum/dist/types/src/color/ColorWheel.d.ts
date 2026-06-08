import { AriaColorWheelProps } from 'react-aria/useColorWheel';
import { DimensionValue, StyleProps } from '@react-types/shared';
import React from 'react';
export interface SpectrumColorWheelProps extends AriaColorWheelProps, Omit<StyleProps, 'width' | 'height'> {
    /** The outer diameter of the ColorWheel. */
    size?: DimensionValue;
}
/**
 * ColorWheels allow users to adjust the hue of an HSL or HSB color value on a circular track.
 */
export declare const ColorWheel: React.ForwardRefExoticComponent<SpectrumColorWheelProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLDivElement, HTMLDivElement>>>;
