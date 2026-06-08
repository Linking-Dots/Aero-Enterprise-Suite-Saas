import { AriaRangeCalendarProps } from 'react-aria/useRangeCalendar';
import { CalendarIdentifier, Calendar as ICalendar } from '@internationalized/date';
import { DateValue } from 'react-stately/useCalendarState';
import { FocusableRef, StyleProps } from '@react-types/shared';
import React, { ReactElement } from 'react';
export interface SpectrumRangeCalendarProps<T extends DateValue> extends Omit<AriaRangeCalendarProps<T>, 'weeksInMonth'>, StyleProps {
    /**
     * The number of months to display at once. Up to 3 months are supported.
     *
     * @default 1
     */
    visibleMonths?: number;
    /**
     * A function to create a new
     * [Calendar](https://react-spectrum.adobe.com/internationalized/date/Calendar.html) object for a
     * given calendar identifier. If not provided, the `createCalendar` function from
     * `@internationalized/date` will be used.
     */
    createCalendar?: (identifier: CalendarIdentifier) => ICalendar;
}
/**
 * RangeCalendars display a grid of days in one or more months and allow users to select a
 * contiguous range of dates.
 */
export declare const RangeCalendar: <T extends DateValue>(props: SpectrumRangeCalendarProps<T> & {
    ref?: FocusableRef<HTMLElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
