import { AriaDateFieldProps } from 'react-aria/useDateField';
import { AriaTimeFieldProps } from 'react-aria/useTimeField';
import { ClassNameOrFunction, ContextValue, RACValidation, RenderProps, SlotProps, StyleRenderProps } from './utils';
import { DateFieldState, DateSegmentType, DateValue, DateSegment as IDateSegment } from 'react-stately/useDateFieldState';
import { GlobalDOMAttributes } from '@react-types/shared';
import { HoverEvents } from '@react-types/shared';
import React, { ReactElement } from 'react';
import { TimeFieldState } from 'react-stately/useTimeFieldState';
import { TimeValue } from 'react-stately/useTimeFieldState';
export interface DateFieldRenderProps {
    /**
     * State of the date field.
     */
    state: DateFieldState;
    /**
     * Whether the date field is invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * Whether the date field is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the date field is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the date field is required.
     *
     * @selector [data-required]
     */
    isRequired: boolean;
}
export interface DateFieldProps<T extends DateValue> extends Omit<AriaDateFieldProps<T>, 'label' | 'description' | 'errorMessage' | 'validationState' | 'validationBehavior'>, RACValidation, RenderProps<DateFieldRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-DateField'
     */
    className?: ClassNameOrFunction<DateFieldRenderProps>;
}
export interface TimeFieldProps<T extends TimeValue> extends Omit<AriaTimeFieldProps<T>, 'label' | 'description' | 'errorMessage' | 'validationState' | 'validationBehavior'>, RACValidation, RenderProps<DateFieldRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-TimeField'
     */
    className?: ClassNameOrFunction<DateFieldRenderProps>;
}
export declare const DateFieldContext: React.Context<ContextValue<DateFieldProps<any>, HTMLDivElement>>;
export declare const TimeFieldContext: React.Context<ContextValue<TimeFieldProps<any>, HTMLDivElement>>;
export declare const DateFieldStateContext: React.Context<DateFieldState | null>;
export declare const TimeFieldStateContext: React.Context<TimeFieldState | null>;
/**
 * A date field allows users to enter and edit date and time values using a keyboard.
 * Each part of a date value is displayed in an individually editable segment.
 */
export declare const DateField: <T extends DateValue>(props: DateFieldProps<T> & React.RefAttributes<HTMLDivElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A time field allows users to enter and edit time values using a keyboard.
 * Each part of a time value is displayed in an individually editable segment.
 */
export declare const TimeField: <T extends TimeValue>(props: TimeFieldProps<T> & React.RefAttributes<HTMLDivElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface DateInputRenderProps {
    /**
     * Whether the date input is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether an element within the date input is focused, either via a mouse or keyboard.
     *
     * @selector [data-focus-within]
     */
    isFocusWithin: boolean;
    /**
     * Whether an element within the date input is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the date input is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the date input is invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
}
export interface DateInputProps extends SlotProps, StyleRenderProps<DateInputRenderProps>, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-DateInput'
     */
    className?: ClassNameOrFunction<DateInputRenderProps>;
    children: (segment: IDateSegment) => ReactElement;
}
/**
 * A date input groups the editable date segments within a date field.
 */
export declare const DateInput: (props: DateInputProps & React.RefAttributes<HTMLDivElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface DateSegmentRenderProps extends Omit<IDateSegment, 'isEditable'> {
    /**
     * Whether the segment is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the segment is focused, either via a mouse or keyboard.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the segment is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the value is a placeholder.
     *
     * @selector [data-placeholder]
     */
    isPlaceholder: boolean;
    /**
     * Whether the segment is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the date field is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the date field is in an invalid state.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * The type of segment. Values include `literal`, `year`, `month`, `day`, etc.
     *
     * @selector [data-type="..."]
     */
    type: DateSegmentType;
}
export interface DateSegmentProps extends RenderProps<DateSegmentRenderProps, 'span'>, HoverEvents, GlobalDOMAttributes<HTMLSpanElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-DateSegment'
     */
    className?: ClassNameOrFunction<DateSegmentRenderProps>;
    segment: IDateSegment;
}
/**
 * A date segment displays an individual unit of a date and time, and allows users to edit
 * the value by typing or using the arrow keys to increment and decrement.
 */
export declare const DateSegment: (props: DateSegmentProps & React.RefAttributes<HTMLSpanElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
