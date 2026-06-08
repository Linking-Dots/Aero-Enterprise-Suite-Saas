import { AriaColorSliderProps } from 'react-aria/useColorSlider';
import { StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface SpectrumColorSliderProps extends AriaColorSliderProps, StyleProps {
    /**
     * Whether the value label is displayed. True by default if there is a label, false by default if
     * not.
     */
    showValueLabel?: boolean;
    /** A ContextualHelp element to place next to the label. */
    contextualHelp?: ReactNode;
}
/**
 * ColorSliders allow users to adjust an individual channel of a color value.
 */
export declare const ColorSlider: React.ForwardRefExoticComponent<SpectrumColorSliderProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLDivElement, HTMLDivElement>>>;
