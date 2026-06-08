import { AriaSwitchProps } from 'react-aria/useSwitch';
import { ClassNameOrFunction, ContextValue, RACValidation, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes, RefObject } from '@react-types/shared';
import { HoverEvents } from '@react-types/shared';
import React from 'react';
import { ToggleState } from 'react-stately/useToggleState';
export interface SwitchProps extends Omit<AriaSwitchProps, 'children' | 'validationState' | 'validationBehavior' | 'isRequired' | 'isInvalid' | 'validate'>, HoverEvents, RenderProps<SwitchRenderProps, 'label'>, SlotProps, Omit<GlobalDOMAttributes<HTMLLabelElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Switch'
     */
    className?: ClassNameOrFunction<SwitchRenderProps>;
    /**
     * A ref for the HTML input element.
     */
    inputRef?: RefObject<HTMLInputElement | null>;
}
export interface SwitchFieldProps extends Omit<AriaSwitchProps, 'children' | 'validationState' | 'validationBehavior'>, RACValidation, RenderProps<SwitchFieldRenderProps>, SlotProps, Omit<GlobalDOMAttributes<HTMLDivElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-SwitchField'
     */
    className?: ClassNameOrFunction<SwitchFieldRenderProps>;
    /**
     * A ref for the HTML input element.
     */
    inputRef?: RefObject<HTMLInputElement | null>;
}
export interface SwitchButtonProps extends HoverEvents, RenderProps<SwitchButtonRenderProps, 'label'>, SlotProps, GlobalDOMAttributes<HTMLLabelElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-SwitchButton'
     */
    className?: ClassNameOrFunction<SwitchButtonRenderProps>;
}
export interface SwitchRenderProps {
    /**
     * Whether the switch is selected.
     *
     * @selector [data-selected]
     */
    isSelected: boolean;
    /**
     * Whether the switch is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the switch is currently in a pressed state.
     *
     * @selector [data-pressed]
     */
    isPressed: boolean;
    /**
     * Whether the switch is focused, either via a mouse or keyboard.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the switch is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the switch is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the switch is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * State of the switch.
     */
    state: ToggleState;
}
export interface SwitchFieldRenderProps {
    /**
     * Whether the switch is selected.
     *
     * @selector [data-selected]
     */
    isSelected: boolean;
    /**
     * Whether the switch is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the switch is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the switch invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * Whether the switch is required.
     *
     * @selector [data-required]
     */
    isRequired: boolean;
    /**
     * State of the switch.
     */
    state: ToggleState;
}
export interface SwitchButtonRenderProps extends SwitchRenderProps {
    /**
     * Whether the switch invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * Whether the switch is required.
     *
     * @selector [data-required]
     */
    isRequired: boolean;
    /**
     * State of the switch.
     */
    state: ToggleState;
}
export declare const SwitchContext: React.Context<ContextValue<SwitchProps, HTMLLabelElement>>;
export declare const SwitchFieldContext: React.Context<ContextValue<SwitchFieldProps, HTMLDivElement>>;
export declare const ToggleStateContext: React.Context<ToggleState | null>;
/**
 * A switch allows a user to turn a setting on or off.
 *
 * @deprecated Use SwitchField + SwitchButton instead.
 */
export declare const Switch: (props: SwitchProps & React.RefAttributes<HTMLLabelElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A switch allows a user to turn a setting on or off, with support for validation and help text.
 */
export declare const SwitchField: (props: SwitchFieldProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A switch button is the clickable area of a switch, including the indicator and label.
 */
export declare const SwitchButton: (props: SwitchButtonProps & React.RefAttributes<HTMLLabelElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
