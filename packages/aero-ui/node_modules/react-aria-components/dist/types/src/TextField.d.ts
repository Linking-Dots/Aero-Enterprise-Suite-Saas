import { AriaTextFieldProps } from 'react-aria/useTextField';
import { ClassNameOrFunction, ContextValue, DOMProps, RACValidation, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes } from '@react-types/shared';
import React from 'react';
export interface TextFieldRenderProps {
    /**
     * Whether the text field is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the value is invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * Whether the text field is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the text field is required.
     *
     * @selector [data-required]
     */
    isRequired: boolean;
}
export interface TextFieldProps extends Omit<AriaTextFieldProps, 'label' | 'placeholder' | 'description' | 'errorMessage' | 'validationState' | 'validationBehavior'>, RACValidation, Omit<DOMProps, 'style' | 'className' | 'render' | 'children'>, SlotProps, RenderProps<TextFieldRenderProps>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-TextField'
     */
    className?: ClassNameOrFunction<TextFieldRenderProps>;
    /** Whether the value is invalid. */
    isInvalid?: boolean;
}
export declare const TextFieldContext: React.Context<ContextValue<TextFieldProps, HTMLDivElement>>;
/**
 * A text field allows a user to enter a plain text value with a keyboard.
 */
export declare const TextField: (props: TextFieldProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
