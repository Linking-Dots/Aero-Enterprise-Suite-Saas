import { AriaToggleButtonProps } from 'react-aria/useToggleButton';
import { ButtonRenderProps } from './Button';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes, Key } from '@react-types/shared';
import { HoverEvents } from '@react-types/shared';
import React from 'react';
import { ToggleState } from 'react-stately/useToggleState';
export interface ToggleButtonRenderProps extends Omit<ButtonRenderProps, 'isPending'> {
    /**
     * Whether the button is currently selected.
     *
     * @selector [data-selected]
     */
    isSelected: boolean;
    /**
     * State of the toggle button.
     */
    state: ToggleState;
}
export interface ToggleButtonProps extends Omit<AriaToggleButtonProps, 'children' | 'elementType' | 'id'>, HoverEvents, SlotProps, RenderProps<ToggleButtonRenderProps, 'button'>, Omit<GlobalDOMAttributes<HTMLDivElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ToggleButton'
     */
    className?: ClassNameOrFunction<ToggleButtonRenderProps>;
    /**
     * When used in a ToggleButtonGroup, an identifier for the item in `selectedKeys`. When used
     * standalone, a DOM id.
     */
    id?: Key;
}
export declare const ToggleButtonContext: React.Context<ContextValue<ToggleButtonProps, HTMLButtonElement>>;
/**
 * A toggle button allows a user to toggle a selection on or off, for example switching between two
 * states or modes.
 */
export declare const ToggleButton: (props: ToggleButtonProps & React.RefAttributes<HTMLButtonElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
