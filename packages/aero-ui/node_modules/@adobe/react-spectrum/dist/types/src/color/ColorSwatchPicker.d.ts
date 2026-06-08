import { Color } from 'react-stately/Color';
import { StyleProps, ValueBase } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface SpectrumColorSwatchPickerProps extends ValueBase<string | Color, Color>, StyleProps {
    /** The ColorSwatches within the ColorSwatchPicker. */
    children: ReactNode;
    /**
     * The amount of padding between the swatches.
     *
     * @default 'regular'
     */
    density?: 'compact' | 'regular' | 'spacious';
    /**
     * The size of the color swatches.
     *
     * @default 'M'
     */
    size?: 'XS' | 'S' | 'M' | 'L';
    /**
     * The corner rounding of the color swatches.
     *
     * @default 'none'
     */
    rounding?: 'none' | 'default' | 'full';
}
/**
 * A ColorSwatchPicker displays a list of color swatches and allows a user to select one of them.
 */
export declare const ColorSwatchPicker: React.ForwardRefExoticComponent<SpectrumColorSwatchPickerProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
