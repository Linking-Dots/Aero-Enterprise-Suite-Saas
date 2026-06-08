import { AriaCalendarProps } from 'react-aria/useCalendar';
import { CalendarIdentifier, Calendar as ICalendar } from '@internationalized/date';
import { DateValue } from 'react-stately/useCalendarState';
import { FocusableRef, StyleProps } from '@react-types/shared';
import React, { ReactElement } from 'react';
export interface SpectrumCalendarProps<T extends DateValue> extends Omit<AriaCalendarProps<T>, 'selectionMode' | 'weeksInMonth'>, StyleProps {
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
 * Calendars display a grid of days in one or more months and allow users to select a single date.
 */
export declare const Calendar: <T extends DateValue>(props: SpectrumCalendarProps<T> & {
    ref?: FocusableRef<HTMLElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
