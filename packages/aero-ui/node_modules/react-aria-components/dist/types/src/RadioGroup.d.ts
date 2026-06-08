import { AriaRadioGroupProps, AriaRadioProps } from 'react-aria/useRadioGroup';
import { ClassNameOrFunction, ContextValue, RACValidation, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes, RefObject } from '@react-types/shared';
import { HoverEvents, Orientation } from '@react-types/shared';
import { RadioGroupState } from 'react-stately/useRadioGroupState';
import React from 'react';
export interface RadioGroupProps extends Omit<AriaRadioGroupProps, 'children' | 'label' | 'description' | 'errorMessage' | 'validationState' | 'validationBehavior'>, RACValidation, RenderProps<RadioGroupRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-RadioGroup'
     */
    className?: ClassNameOrFunction<RadioGroupRenderProps>;
}
export interface RadioProps extends Omit<AriaRadioProps, 'children'>, HoverEvents, RenderProps<RadioRenderProps, 'label'>, SlotProps, Omit<GlobalDOMAttributes<HTMLLabelElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Radio'
     */
    className?: ClassNameOrFunction<RadioRenderProps>;
    /**
     * A ref for the HTML input element.
     */
    inputRef?: RefObject<HTMLInputElement | null>;
}
export interface RadioFieldProps extends Omit<AriaRadioProps, 'children'>, RenderProps<RadioFieldRenderProps>, SlotProps, Omit<GlobalDOMAttributes<HTMLDivElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-RadioField'
     */
    className?: ClassNameOrFunction<RadioFieldRenderProps>;
    /**
     * A ref for the HTML input element.
     */
    inputRef?: RefObject<HTMLInputElement | null>;
}
export interface RadioButtonProps extends HoverEvents, RenderProps<RadioButtonRenderProps, 'label'>, SlotProps, Omit<GlobalDOMAttributes<HTMLLabelElement>, 'onClick'> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-RadioButton'
     */
    className?: ClassNameOrFunction<RadioButtonRenderProps>;
}
export interface RadioGroupRenderProps {
    /**
     * The orientation of the radio group.
     *
     * @selector [data-orientation="horizontal | vertical"]
     */
    orientation: Orientation;
    /**
     * Whether the radio group is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the radio group is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the radio group is required.
     *
     * @selector [data-required]
     */
    isRequired: boolean;
    /**
     * Whether the radio group is invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * State of the radio group.
     */
    state: RadioGroupState;
}
export interface RadioRenderProps {
    /**
     * Whether the radio is selected.
     *
     * @selector [data-selected]
     */
    isSelected: boolean;
    /**
     * Whether the radio is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the radio is currently in a pressed state.
     *
     * @selector [data-pressed]
     */
    isPressed: boolean;
    /**
     * Whether the radio is focused, either via a mouse or keyboard.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the radio is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the radio is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the radio is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the radio is invalid.
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
export interface RadioFieldRenderProps {
    /**
     * Whether the radio is selected.
     *
     * @selector [data-selected]
     */
    isSelected: boolean;
    /**
     * Whether the radio is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the radio is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the radio is invalid.
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
export interface RadioButtonRenderProps extends RadioRenderProps {
}
export declare const RadioGroupContext: React.Context<ContextValue<RadioGroupProps, HTMLDivElement>>;
export declare const RadioContext: React.Context<ContextValue<Partial<RadioProps>, HTMLLabelElement>>;
export declare const RadioFieldContext: React.Context<ContextValue<Partial<RadioFieldProps>, HTMLDivElement>>;
export declare const RadioGroupStateContext: React.Context<RadioGroupState | null>;
/**
 * A radio group allows a user to select a single item from a list of mutually exclusive options.
 */
export declare const RadioGroup: (props: RadioGroupProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A radio represents an individual option within a radio group.
 *
 * @deprecated Use RadioField + RadioButton instead.
 */
export declare const Radio: (props: RadioProps & React.RefAttributes<HTMLLabelElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A RadioField represents an individual option within a radio group, containing a RadioButton and
 * optional description.
 */
export declare const RadioField: (props: RadioFieldProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A RadioButton is the clickable area of a radio, including the indicator and label.
 */
export declare const RadioButton: (props: RadioButtonProps & React.RefAttributes<HTMLLabelElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
