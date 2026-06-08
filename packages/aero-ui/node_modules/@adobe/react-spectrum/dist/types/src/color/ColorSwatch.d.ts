import { AriaColorSwatchProps } from 'react-aria/useColorSwatch';
import { Color } from 'react-stately/Color';
import { StyleProps } from '@react-types/shared';
import React, { JSX, ReactElement } from 'react';
export interface SpectrumColorSwatchProps extends AriaColorSwatchProps, StyleProps {
    /**
     * The size of the ColorSwatch.
     *
     * @default 'M'
     */
    size?: 'XS' | 'S' | 'M' | 'L';
    /**
     * The corner rounding of the ColorSwatch.
     *
     * @default 'default'
     */
    rounding?: 'default' | 'none' | 'full';
}
interface SpectrumColorSwatchContextValue extends Pick<SpectrumColorSwatchProps, 'size' | 'rounding'> {
    useWrapper: (swatch: ReactElement, color: Color, rounding: SpectrumColorSwatchProps['rounding']) => JSX.Element;
}
export declare const SpectrumColorSwatchContext: React.Context<SpectrumColorSwatchContextValue | null>;
/**
 * A ColorSwatch displays a preview of a selected color.
 */
export declare const ColorSwatch: React.ForwardRefExoticComponent<SpectrumColorSwatchProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
export {};
