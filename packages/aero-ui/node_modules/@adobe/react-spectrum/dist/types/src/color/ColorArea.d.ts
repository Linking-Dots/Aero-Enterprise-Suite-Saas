import { AriaColorAreaProps } from 'react-aria/useColorArea';
import { DimensionValue, FocusableRef, StyleProps } from '@react-types/shared';
import React, { ReactElement } from 'react';
export interface SpectrumColorAreaProps extends AriaColorAreaProps, Omit<StyleProps, 'width' | 'height'> {
    /** Size of the Color Area. */
    size?: DimensionValue;
}
/**
 * ColorArea allows users to adjust two channels of an RGB, HSL or HSB color value against a
 * two-dimensional gradient background.
 */
export declare const ColorArea: (props: SpectrumColorAreaProps & {
    ref?: FocusableRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
