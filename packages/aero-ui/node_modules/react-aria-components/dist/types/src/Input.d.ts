import { ClassNameOrFunction, ContextValue, StyleRenderProps } from './utils';
import { HoverEvents } from '@react-types/shared';
import React, { InputHTMLAttributes } from 'react';
export interface InputRenderProps {
    /**
     * Whether the input is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the input is focused, either via a mouse or keyboard.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the input is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the input is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the input is invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
}
export interface InputProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'className' | 'style'>, HoverEvents, StyleRenderProps<InputRenderProps, 'input'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Input'
     */
    className?: ClassNameOrFunction<InputRenderProps>;
    /**
     * Temporary text that occupies the text input when it is empty.
     * See [MDN](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Attributes/placeholder).
     */
    placeholder?: string;
}
export declare const InputContext: React.Context<ContextValue<InputProps, HTMLInputElement>>;
/**
 * An input allows a user to input text.
 */
export declare const Input: (props: InputProps & React.RefAttributes<HTMLInputElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
