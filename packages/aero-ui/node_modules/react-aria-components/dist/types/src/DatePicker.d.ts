import { AriaDatePickerProps } from 'react-aria/useDatePicker';
import { AriaDateRangePickerProps } from 'react-aria/useDateRangePicker';
import { ClassNameOrFunction, ContextValue, RACValidation, RenderProps, SlotProps } from './utils';
import { DatePickerState, DatePickerStateOptions, DateValue } from 'react-stately/useDatePickerState';
import { DateRangePickerState, DateRangePickerStateOptions } from 'react-stately/useDateRangePickerState';
import { GlobalDOMAttributes } from '@react-types/shared';
import React from 'react';
export interface DatePickerRenderProps {
    /**
     * Whether an element within the date picker is focused, either via a mouse or keyboard.
     *
     * @selector [data-focus-within]
     */
    isFocusWithin: boolean;
    /**
     * Whether an element within the date picker is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the date picker is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the date picker is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the date picker is invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * Whether the date picker is required.
     *
     * @selector [data-required]
     */
    isRequired: boolean;
    /**
     * Whether the date picker's popover is currently open.
     *
     * @selector [data-open]
     */
    isOpen: boolean;
    /**
     * State of the date picker.
     */
    state: DatePickerState;
}
export interface DateRangePickerRenderProps extends Omit<DatePickerRenderProps, 'state'> {
    /**
     * State of the date range picker.
     */
    state: DateRangePickerState;
}
export interface DatePickerProps<T extends DateValue> extends Omit<AriaDatePickerProps<T>, 'label' | 'description' | 'errorMessage' | 'validationState' | 'validationBehavior'>, Pick<DatePickerStateOptions<T>, 'shouldCloseOnSelect'>, RACValidation, RenderProps<DatePickerRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-DatePicker'
     */
    className?: ClassNameOrFunction<DatePickerRenderProps>;
}
export interface DateRangePickerProps<T extends DateValue> extends Omit<AriaDateRangePickerProps<T>, 'label' | 'description' | 'errorMessage' | 'validationState' | 'validationBehavior'>, Pick<DateRangePickerStateOptions<T>, 'shouldCloseOnSelect'>, RACValidation, RenderProps<DateRangePickerRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-DateRangePicker'
     */
    className?: ClassNameOrFunction<DateRangePickerRenderProps>;
}
export declare const DatePickerContext: React.Context<ContextValue<DatePickerProps<any>, HTMLDivElement>>;
export declare const DateRangePickerContext: React.Context<ContextValue<DateRangePickerProps<any>, HTMLDivElement>>;
export declare const DatePickerStateContext: React.Context<DatePickerState | null>;
export declare const DateRangePickerStateContext: React.Context<DateRangePickerState | null>;
/**
 * A date picker combines a DateField and a Calendar popover to allow users to enter or select a
 * date and time value.
 */
export declare const DatePicker: <T extends DateValue>(props: DatePickerProps<T> & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A date range picker combines two DateFields and a RangeCalendar popover to allow
 * users to enter or select a date and time range.
 */
export declare const DateRangePicker: <T extends DateValue>(props: DateRangePickerProps<T> & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
