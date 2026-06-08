import { AriaNumberFieldProps } from 'react-aria/useNumberField';
import { InputDOMProps, SpectrumFieldValidation, SpectrumLabelableProps, StyleProps } from '@react-types/shared';
import React from 'react';
export interface SpectrumNumberFieldProps extends Omit<AriaNumberFieldProps, 'placeholder' | 'isInvalid' | 'validationState'>, SpectrumFieldValidation<number>, InputDOMProps, StyleProps, SpectrumLabelableProps {
    /** Whether the numberfield should be displayed with a quiet style. */
    isQuiet?: boolean;
    /**
     * Whether to hide the increment and decrement buttons.
     *
     * @default false
     */
    hideStepper?: boolean;
}
/**
 * NumberFields allow users to enter a number, and increment or decrement the value using stepper
 * buttons.
 */
export declare const NumberField: React.ForwardRefExoticComponent<SpectrumNumberFieldProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLElement, HTMLElement>>>;
