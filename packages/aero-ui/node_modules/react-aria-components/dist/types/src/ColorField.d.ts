import { AriaColorFieldProps } from 'react-aria/useColorField';
import { ClassNameOrFunction, ContextValue, RACValidation, RenderProps, SlotProps } from './utils';
import { ColorChannel, ColorSpace } from 'react-stately/Color';
import { ColorFieldState } from 'react-stately/useColorFieldState';
import { GlobalDOMAttributes, InputDOMProps } from '@react-types/shared';
import React from 'react';
export interface ColorFieldRenderProps {
    /**
     * Whether the color field is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the color field is invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * Whether the color field is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the color field is required.
     *
     * @selector [data-required]
     */
    isRequired: boolean;
    /**
     * The color channel that this field edits, or "hex" if no `channel` prop is set.
     *
     * @selector [data-channel="hex | hue | saturation | ..."]
     */
    channel: ColorChannel | 'hex';
    /**
     * State of the color field.
     */
    state: ColorFieldState;
}
export interface ColorFieldProps extends Omit<AriaColorFieldProps, 'label' | 'placeholder' | 'description' | 'errorMessage' | 'validationState' | 'validationBehavior'>, RACValidation, InputDOMProps, RenderProps<ColorFieldRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ColorField'
     */
    className?: ClassNameOrFunction<ColorFieldRenderProps>;
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
}
export declare const ColorFieldContext: React.Context<ContextValue<ColorFieldProps, HTMLDivElement>>;
export declare const ColorFieldStateContext: React.Context<ColorFieldState | null>;
/**
 * A color field allows users to edit a hex color or individual color channel value.
 */
export declare const ColorField: React.ForwardRefExoticComponent<ColorFieldProps & React.RefAttributes<HTMLDivElement>>;
