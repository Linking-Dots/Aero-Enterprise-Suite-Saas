import { AriaColorFieldProps } from 'react-aria/useColorField';
import { Color, ColorChannel, ColorSpace } from 'react-stately/Color';
import React from 'react';
import { SpectrumFieldValidation, SpectrumLabelableProps, SpectrumTextInputBase, StyleProps } from '@react-types/shared';
import { TextFieldRef } from '../textfield/TextField';
export interface SpectrumColorFieldProps extends SpectrumTextInputBase, Omit<AriaColorFieldProps, 'isInvalid' | 'validationState'>, SpectrumFieldValidation<Color | null>, SpectrumLabelableProps, StyleProps {
    /**
     * The color channel that this field edits. If not provided,
     * the color is edited as a hex value.
     */
    channel?: ColorChannel;
    /**
     * The color space that the color field operates in if a `channel` prop is provided.
     * If no `channel` is provided, the color field always displays the color as an RGB hex value.
     */
    colorSpace?: ColorSpace;
    /** Whether the ColorField should be displayed with a quiet style. */
    isQuiet?: boolean;
}
/**
 * A color field allows users to edit a hex color or individual color channel value.
 */
export declare const ColorField: React.ForwardRefExoticComponent<SpectrumColorFieldProps & React.RefAttributes<TextFieldRef<HTMLInputElement>>>;
