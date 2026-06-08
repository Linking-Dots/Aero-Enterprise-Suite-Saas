import { AriaButtonProps } from 'react-aria/useButton';
import { CalendarPropsBase, CalendarState } from 'react-stately/useCalendarState';
import { DOMProps, RefObject, StyleProps } from '@react-types/shared';
import { RangeCalendarState } from 'react-stately/useRangeCalendarState';
import { HTMLAttributes, JSX } from 'react';
interface CalendarBaseProps<T extends CalendarState | RangeCalendarState> extends CalendarPropsBase, DOMProps, StyleProps {
    state: T;
    visibleMonths?: number;
    calendarProps: HTMLAttributes<HTMLElement>;
    nextButtonProps: AriaButtonProps;
    prevButtonProps: AriaButtonProps;
    errorMessageProps: HTMLAttributes<HTMLElement>;
    calendarRef: RefObject<HTMLDivElement | null>;
}
export declare function CalendarBase<T extends CalendarState | RangeCalendarState>(props: CalendarBaseProps<T>): JSX.Element;
export {};
