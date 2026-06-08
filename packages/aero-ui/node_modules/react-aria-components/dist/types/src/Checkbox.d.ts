import { AriaCheckboxGroupProps } from 'react-aria/useCheckboxGroup';
import { AriaCheckboxProps } from 'react-aria/useCheckbox';
import { CheckboxGroupState } from 'react-stately/useCheckboxGroupState';
import { ClassNameOrFunction, ContextValue, RACValidation, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes, RefObject } from '@react-types/shared';
import { HoverEvents } from '@react-types/shared';
import React from 'react';
export interface CheckboxGroupProps extends Omit<AriaCheckboxGroupProps, 'children' | 'label' | 'description' | 'errorMessage' | 'validationState' | 'validationBehavior'>, RACValidation, RenderProps<CheckboxGroupRenderProps, 'div'>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-CheckboxGroup'
     */
    className?: ClassNameOrFunction<CheckboxGroupRenderProps>;
}
export interface CheckboxProps extends Omit<AriaCheckboxProps, 'children' | 'validationState' | 'validationBehavior'>, HoverEvents, RACValidation, RenderProps<CheckboxRenderProps, 'label'>, SlotProps, Omit<GlobalDOMAttributes<HTMLLabelElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Checkbox'
     */
    className?: ClassNameOrFunction<CheckboxRenderProps>;
    /**
     * A ref for the HTML input element.
     */
    inputRef?: RefObject<HTMLInputElement | null>;
}
export interface CheckboxFieldProps extends Omit<AriaCheckboxProps, 'children' | 'validationState' | 'validationBehavior'>, RACValidation, RenderProps<CheckboxFieldRenderProps>, SlotProps, Omit<GlobalDOMAttributes<HTMLDivElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-CheckboxField'
     */
    className?: ClassNameOrFunction<CheckboxFieldRenderProps>;
    /**
     * A ref for the HTML input element.
     */
    inputRef?: RefObject<HTMLInputElement | null>;
}
export interface CheckboxButtonProps extends HoverEvents, RenderProps<CheckboxButtonRenderProps, 'label'>, SlotProps, GlobalDOMAttributes<HTMLLabelElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-CheckboxButton'
     */
    className?: ClassNameOrFunction<CheckboxButtonRenderProps>;
}
export interface CheckboxGroupRenderProps {
    /**
     * Whether the checkbox group is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the checkbox group is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the checkbox group is required.
     *
     * @selector [data-required]
     */
    isRequired: boolean;
    /**
     * Whether the checkbox group is invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * State of the checkbox group.
     */
    state: CheckboxGroupState;
}
export interface CheckboxRenderProps {
    /**
     * Whether the checkbox is selected.
     *
     * @selector [data-selected]
     */
    isSelected: boolean;
    /**
     * Whether the checkbox is indeterminate.
     *
     * @selector [data-indeterminate]
     */
    isIndeterminate: boolean;
    /**
     * Whether the checkbox is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the checkbox is currently in a pressed state.
     *
     * @selector [data-pressed]
     */
    isPressed: boolean;
    /**
     * Whether the checkbox is focused, either via a mouse or keyboard.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the checkbox is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the checkbox is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the checkbox is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the checkbox invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * Whether the checkbox is required.
     *
     * @selector [data-required]
     */
    isRequired: boolean;
}
export interface CheckboxFieldRenderProps {
    /**
     * Whether the checkbox is selected.
     *
     * @selector [data-selected]
     */
    isSelected: boolean;
    /**
     * Whether the checkbox is indeterminate.
     *
     * @selector [data-indeterminate]
     */
    isIndeterminate: boolean;
    /**
     * Whether the checkbox is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the checkbox is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the checkbox invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * Whether the checkbox is required.
     *
     * @selector [data-required]
     */
    isRequired: boolean;
}
export interface CheckboxButtonRenderProps extends CheckboxRenderProps {
}
export declare const CheckboxContext: React.Context<ContextValue<CheckboxProps, HTMLLabelElement>>;
export declare const CheckboxFieldContext: React.Context<ContextValue<CheckboxFieldProps, HTMLDivElement>>;
export declare const CheckboxGroupContext: React.Context<ContextValue<CheckboxGroupProps, HTMLDivElement>>;
export declare const CheckboxGroupStateContext: React.Context<CheckboxGroupState | null>;
/**
 * A checkbox group allows a user to select multiple items from a list of options.
 */
export declare const CheckboxGroup: (props: CheckboxGroupProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A checkbox allows a user to select an item, with support for validation and help text.
 */
export declare const CheckboxField: (props: CheckboxFieldProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A checkbox allows a user to select multiple items from a list of individual items, or
 * to mark one individual item as selected.
 *
 * @deprecated Use CheckboxField + CheckboxButton instead.
 */
export declare const Checkbox: (props: CheckboxProps & React.RefAttributes<HTMLLabelElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A checkbox button is the clickable area of a checkbox, including the indicator and label.
 */
export declare const CheckboxButton: (props: CheckboxButtonProps & React.RefAttributes<HTMLLabelElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
