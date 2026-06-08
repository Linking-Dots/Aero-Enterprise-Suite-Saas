import { AriaButtonProps } from 'react-aria/useButton';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes } from '@react-types/shared';
import { HoverEvents } from '@react-types/shared';
import React from 'react';
export interface ButtonRenderProps {
    /**
     * Whether the button is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the button is currently in a pressed state.
     *
     * @selector [data-pressed]
     */
    isPressed: boolean;
    /**
     * Whether the button is focused, either via a mouse or keyboard.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the button is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the button is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the button is currently in a pending state.
     *
     * @selector [data-pending]
     */
    isPending: boolean;
}
export interface ButtonProps extends Omit<AriaButtonProps, 'children' | 'href' | 'target' | 'rel' | 'elementType'>, HoverEvents, SlotProps, RenderProps<ButtonRenderProps, 'button'>, Omit<GlobalDOMAttributes<HTMLButtonElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Button'
     */
    className?: ClassNameOrFunction<ButtonRenderProps>;
    /**
     * Whether the button is in a pending state. This disables press and hover events
     * while retaining focusability, and announces the pending state to screen readers.
     */
    isPending?: boolean;
}
interface ButtonContextValue extends ButtonProps {
    isPressed?: boolean;
}
export declare const ButtonContext: React.Context<ContextValue<ButtonContextValue, HTMLButtonElement>>;
/**
 * A button allows a user to perform an action, with mouse, touch, and keyboard interactions.
 */
export declare const Button: (props: ButtonProps & React.RefAttributes<HTMLButtonElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export {};
