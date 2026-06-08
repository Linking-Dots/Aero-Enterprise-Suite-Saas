import { AriaDatePickerProps, DateValue } from 'react-aria/useDatePicker';
import { CalendarIdentifier, Calendar as ICalendar } from '@internationalized/date';
import { FocusableRef, SpectrumLabelableProps, StyleProps } from '@react-types/shared';
import React, { ReactElement } from 'react';
import { SpectrumDateFieldBase } from './DateField';
import '@adobe/spectrum-css-temp/components/textfield/vars.css';
export interface SpectrumDatePickerBase<T extends DateValue> extends SpectrumDateFieldBase<T>, SpectrumLabelableProps, StyleProps {
    /**
     * The maximum number of months to display at once in the calendar popover, if screen space
     * permits.
     *
     * @default 1
     */
    maxVisibleMonths?: number;
    /**
     * Whether the calendar popover should automatically flip direction when space is limited.
     *
     * @default true
     */
    shouldFlip?: boolean;
    /**
     * A function to create a new
     * [Calendar](https://react-spectrum.adobe.com/internationalized/date/Calendar.html) object for a
     * given calendar identifier. This will be used for the popover calendar. If not provided, the
     * `createCalendar` function from `@internationalized/date` will be used.
     */
    createCalendar?: (identifier: CalendarIdentifier) => ICalendar;
}
export interface SpectrumDatePickerProps<T extends DateValue> extends Omit<AriaDatePickerProps<T>, 'isInvalid' | 'validationState' | 'autoComplete'>, SpectrumDatePickerBase<T> {
}
/**
 * DatePickers combine a DateField and a Calendar popover to allow users to enter or select a date
 * and time value.
 */
export declare const DatePicker: <T extends DateValue>(props: SpectrumDatePickerProps<T> & {
    ref?: FocusableRef<HTMLElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
