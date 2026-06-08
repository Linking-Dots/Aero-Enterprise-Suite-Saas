import { ClassNameOrFunction, RenderProps } from './utils';
import { DOMProps, GlobalDOMAttributes, ValidationResult } from '@react-types/shared';
import React from 'react';
export declare const FieldErrorContext: React.Context<ValidationResult | null>;
export interface FieldErrorRenderProps extends ValidationResult {
}
export interface FieldErrorProps extends RenderProps<FieldErrorRenderProps>, DOMProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-FieldError'
     */
    className?: ClassNameOrFunction<FieldErrorRenderProps>;
    /**
     * The HTML element type to render. Defaults to `'span'`.
     * Set to `'div'` when using block-level children (e.g. `<ul>`) to avoid invalid HTML.
     *
     * @default 'span'
     */
    elementType?: string;
}
/**
 * A FieldError displays validation errors for a form field.
 */
export declare const FieldError: React.ForwardRefExoticComponent<FieldErrorProps & React.RefAttributes<HTMLElement>>;
