import { AriaCalendarCellProps } from 'react-aria/useCalendar';
import { CalendarDate } from '@internationalized/date';
import { CalendarState } from 'react-stately/useCalendarState';
import { RangeCalendarState } from 'react-stately/useRangeCalendarState';
import { JSX } from 'react';
interface CalendarCellProps extends AriaCalendarCellProps {
    state: CalendarState | RangeCalendarState;
    currentMonth: CalendarDate;
    firstDayOfWeek?: 'sun' | 'mon' | 'tue' | 'wed' | 'thu' | 'fri' | 'sat';
}
export declare function CalendarCell({ state, currentMonth, firstDayOfWeek, ...props }: CalendarCellProps): JSX.Element;
export {};
