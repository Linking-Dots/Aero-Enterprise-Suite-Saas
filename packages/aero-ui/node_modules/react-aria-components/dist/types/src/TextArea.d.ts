import { ClassNameOrFunction, ContextValue, StyleRenderProps } from './utils';
import { HoverEvents } from '@react-types/shared';
import { InputRenderProps } from './Input';
import React, { TextareaHTMLAttributes } from 'react';
export interface TextAreaProps extends Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'className' | 'style'>, HoverEvents, StyleRenderProps<InputRenderProps, 'textarea'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-TextArea'
     */
    className?: ClassNameOrFunction<InputRenderProps>;
}
export declare const TextAreaContext: React.Context<ContextValue<TextAreaProps, HTMLTextAreaElement>>;
/**
 * A textarea allows a user to input mult-line text.
 */
export declare const TextArea: React.ForwardRefExoticComponent<TextAreaProps & React.RefAttributes<HTMLTextAreaElement>>;
