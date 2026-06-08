import { ContextValue, DOMProps, DOMRenderProps } from './utils';
import { GlobalDOMAttributes, FormProps as SharedFormProps } from '@react-types/shared';
import React from 'react';
export interface FormProps extends SharedFormProps, DOMProps, DOMRenderProps<'form', undefined>, GlobalDOMAttributes<HTMLFormElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-Form'
     */
    className?: string;
    /**
     * Whether to use native HTML form validation to prevent form submission
     * when a field value is missing or invalid, or mark fields as required
     * or invalid via ARIA.
     *
     * @default 'native'
     */
    validationBehavior?: 'aria' | 'native';
}
export declare const FormContext: React.Context<ContextValue<FormProps, HTMLFormElement>>;
/**
 * A form is a group of inputs that allows users to submit data to a server,
 * with support for providing field validation errors.
 */
export declare const Form: React.ForwardRefExoticComponent<FormProps & React.RefAttributes<HTMLFormElement>>;
