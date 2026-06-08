import { AriaDateRangePickerProps, DateValue } from 'react-aria/useDateRangePicker';
import { FocusableRef } from '@react-types/shared';
import React, { ReactElement } from 'react';
import { SpectrumDatePickerBase } from './DatePicker';
export interface SpectrumDateRangePickerProps<T extends DateValue> extends Omit<AriaDateRangePickerProps<T>, 'isInvalid' | 'validationState'>, Omit<SpectrumDatePickerBase<T>, 'validate'> {
}
/**
 * DateRangePickers combine two DateFields and a RangeCalendar popover to allow users
 * to enter or select a date and time range.
 */
export declare const DateRangePicker: <T extends DateValue>(props: SpectrumDateRangePickerProps<T> & {
    ref?: FocusableRef<HTMLElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
