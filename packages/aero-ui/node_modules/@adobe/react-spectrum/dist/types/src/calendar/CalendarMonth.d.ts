import { CalendarDate } from '@internationalized/date';
import { CalendarPropsBase, CalendarState } from 'react-stately/useCalendarState';
import { DOMProps, StyleProps } from '@react-types/shared';
import { RangeCalendarState } from 'react-stately/useRangeCalendarState';
import { JSX } from 'react';
interface CalendarMonthProps extends CalendarPropsBase, DOMProps, StyleProps {
    state: CalendarState | RangeCalendarState;
    startDate: CalendarDate;
}
export declare function CalendarMonth(props: CalendarMonthProps): JSX.Element;
export {};
