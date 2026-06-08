import { AriaNumberFieldProps } from 'react-aria/useNumberField';
import { ClassNameOrFunction, ContextValue, RACValidation, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes, InputDOMProps } from '@react-types/shared';
import { NumberFieldState } from 'react-stately/useNumberFieldState';
import React from 'react';
export interface NumberFieldRenderProps {
    /**
     * Whether the number field is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the number field is invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * Whether the number field is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the number field is required.
     *
     * @selector [data-required]
     */
    isRequired: boolean;
    /**
     * State of the number field.
     */
    state: NumberFieldState;
}
export interface NumberFieldProps extends Omit<AriaNumberFieldProps, 'label' | 'placeholder' | 'description' | 'errorMessage' | 'validationState' | 'validationBehavior'>, RACValidation, InputDOMProps, RenderProps<NumberFieldRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-NumberField'
     */
    className?: ClassNameOrFunction<NumberFieldRenderProps>;
}
export declare const NumberFieldContext: React.Context<ContextValue<NumberFieldProps, HTMLDivElement>>;
export declare const NumberFieldStateContext: React.Context<NumberFieldState | null>;
/**
 * A number field allows a user to enter a number, and increment or decrement the value using
 * stepper buttons.
 */
export declare const NumberField: (props: NumberFieldProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
