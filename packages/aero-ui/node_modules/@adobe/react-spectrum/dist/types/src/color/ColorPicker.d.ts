import { AriaLabelingProps, ValueBase } from '@react-types/shared';
import { Color } from 'react-stately/Color';
import React, { ReactNode } from 'react';
export interface SpectrumColorPickerProps extends ValueBase<string | Color, Color>, AriaLabelingProps {
    /** A visual label for the color picker. */
    label?: ReactNode;
    /** The contents of the color picker popover, e.g. `<ColorEditor />`. */
    children?: ReactNode;
    /**
     * The size of the color swatch.
     *
     * @default 'M'
     */
    size?: 'XS' | 'S' | 'M' | 'L';
    /**
     * The corner rounding of the color swatch.
     *
     * @default 'default'
     */
    rounding?: 'default' | 'none' | 'full';
}
/**
 * A ColorPicker combines a swatch with a customizable popover for editing a color.
 */
export declare const ColorPicker: React.ForwardRefExoticComponent<SpectrumColorPickerProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLButtonElement, HTMLButtonElement>>>;
