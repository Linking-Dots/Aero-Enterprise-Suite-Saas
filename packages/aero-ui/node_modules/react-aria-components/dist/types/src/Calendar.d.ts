import { CalendarHeadingProps as AriaCalendarHeadingProps, CalendarMonthPickerProps as AriaCalendarMonthPickerProps, AriaCalendarProps, CalendarYearPickerProps as AriaCalendarYearPickerProps, CalendarMonthPickerAria, CalendarYearPickerAria } from 'react-aria/useCalendar';
import { AriaRangeCalendarProps } from 'react-aria/useRangeCalendar';
import { CalendarDate, CalendarIdentifier, DateDuration, Calendar as ICalendar } from '@internationalized/date';
import { CalendarSelectionMode, CalendarState } from 'react-stately/useCalendarState';
import { ClassNameOrFunction, ContextValue, DOMProps, DOMRenderProps, RenderProps, SlotProps, StyleProps } from './utils';
import { DateValue } from 'react-stately/useRangeCalendarState';
import { GlobalDOMAttributes, HoverEvents } from '@react-types/shared';
import { HeadingProps } from './Heading';
import { RangeCalendarState } from 'react-stately/useRangeCalendarState';
import React, { JSX, ReactElement } from 'react';
export interface CalendarRenderProps<M extends CalendarSelectionMode = 'single'> {
    /**
     * Whether the calendar is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * State of the calendar.
     */
    state: CalendarState<M>;
    /**
     * Whether the calendar is invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
}
export interface RangeCalendarRenderProps extends Omit<CalendarRenderProps, 'state'> {
    /**
     * State of the range calendar.
     */
    state: RangeCalendarState;
}
export interface CalendarProps<T extends DateValue, M extends CalendarSelectionMode = 'single'> extends Omit<AriaCalendarProps<T, M>, 'errorMessage' | 'validationState'>, RenderProps<CalendarRenderProps<M>, 'div'>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Calendar'
     */
    className?: ClassNameOrFunction<CalendarRenderProps<M>>;
    /**
     * The amount of days that will be displayed at once. This affects how pagination works.
     *
     * @default { months: 1 }
     */
    visibleDuration?: DateDuration;
    /**
     * A function to create a new
     * [Calendar](https://react-spectrum.adobe.com/internationalized/date/Calendar.html) object for a
     * given calendar identifier. If not provided, the `createCalendar` function from
     * `@internationalized/date` will be used.
     */
    createCalendar?: (identifier: CalendarIdentifier) => ICalendar;
}
export interface RangeCalendarProps<T extends DateValue> extends Omit<AriaRangeCalendarProps<T>, 'errorMessage' | 'validationState'>, RenderProps<RangeCalendarRenderProps, 'div'>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-RangeCalendar'
     */
    className?: ClassNameOrFunction<RangeCalendarRenderProps>;
    /**
     * The amount of days that will be displayed at once. This affects how pagination works.
     *
     * @default { months: 1 }
     */
    visibleDuration?: DateDuration;
    /**
     * A function to create a new
     * [Calendar](https://react-spectrum.adobe.com/internationalized/date/Calendar.html) object for a
     * given calendar identifier. If not provided, the `createCalendar` function from
     * `@internationalized/date` will be used.
     */
    createCalendar?: (identifier: CalendarIdentifier) => ICalendar;
}
export declare const CalendarContext: React.Context<ContextValue<CalendarProps<any, CalendarSelectionMode>, HTMLDivElement>>;
export declare const RangeCalendarContext: React.Context<ContextValue<RangeCalendarProps<any>, HTMLDivElement>>;
export declare const CalendarStateContext: React.Context<CalendarState<CalendarSelectionMode> | null>;
export declare const RangeCalendarStateContext: React.Context<RangeCalendarState<DateValue> | null>;
/**
 * A calendar displays one or more date grids and allows users to select a single date.
 */
export declare const Calendar: <T extends DateValue, M extends CalendarSelectionMode = "single">(props: CalendarProps<T, M> & React.RefAttributes<HTMLDivElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A range calendar displays one or more date grids and allows users to select a contiguous range of
 * dates.
 */
export declare const RangeCalendar: <T extends DateValue>(props: RangeCalendarProps<T> & React.RefAttributes<HTMLDivElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface CalendarCellRenderProps {
    /** The date that the cell represents. */
    date: CalendarDate;
    /** The day number formatted according to the current locale. */
    formattedDate: string;
    /**
     * Whether the cell is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether the cell is currently being pressed.
     *
     * @selector [data-pressed]
     */
    isPressed: boolean;
    /**
     * Whether the cell is selected.
     *
     * @selector [data-selected]
     */
    isSelected: boolean;
    /**
     * Whether the cell is the first date in a range selection.
     *
     * @selector [data-selection-start]
     */
    isSelectionStart: boolean;
    /**
     * Whether the cell is the last date in a range selection.
     *
     * @selector [data-selection-end]
     */
    isSelectionEnd: boolean;
    /**
     * Whether the cell is focused.
     *
     * @selector [data-focused]
     */
    isFocused: boolean;
    /**
     * Whether the cell is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the cell is disabled, according to the calendar's `minValue`, `maxValue`, and
     * `isDisabled` props. Disabled dates are not focusable, and cannot be selected by the user. They
     * are typically displayed with a dimmed appearance.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the cell is outside the visible range of the calendar.
     * For example, dates before the first day of a month in the same week.
     *
     * @selector [data-outside-visible-range]
     */
    isOutsideVisibleRange: boolean;
    /**
     * Whether the cell is outside the current month.
     *
     * @selector [data-outside-month]
     */
    isOutsideMonth: boolean;
    /**
     * Whether the cell is unavailable, according to the calendar's `isDateUnavailable` prop.
     * Unavailable dates remain focusable, but cannot be selected by the user. They should be
     * displayed with a visual affordance to indicate they are unavailable, such as a different color
     * or a strikethrough.
     *
     * Note that because they are focusable, unavailable dates must meet a 4.5:1 color contrast ratio,
     * [as defined by WCAG](https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html).
     *
     * @selector [data-unavailable]
     */
    isUnavailable: boolean;
    /**
     * Whether the cell is part of an invalid selection.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * Whether the cell is today.
     *
     * @selector [data-today]
     */
    isToday: boolean;
}
export interface CalendarGridProps extends StyleProps, DOMRenderProps<'table', undefined>, GlobalDOMAttributes<HTMLTableElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-CalendarGrid'
     */
    className?: string;
    /**
     * Either a function to render calendar cells for each date in the month,
     * or children containing a `<CalendarGridHeader>`` and `<CalendarGridBody>`
     * when additional customization is needed.
     */
    children?: ReactElement | ReactElement[] | ((date: CalendarDate) => ReactElement);
    /**
     * An offset from the beginning of the visible date range that this
     * CalendarGrid should display. Useful when displaying more than one
     * month at a time.
     */
    offset?: DateDuration;
    /**
     * The style of weekday names to display in the calendar grid header,
     * e.g. single letter, abbreviation, or full day name.
     *
     * @default 'narrow'
     */
    weekdayStyle?: 'narrow' | 'short' | 'long';
}
/**
 * A calendar grid displays a single grid of days within a calendar or range calendar which
 * can be keyboard navigated and selected by the user.
 */
export declare const CalendarGrid: (props: CalendarGridProps & React.RefAttributes<HTMLTableElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface CalendarGridHeaderProps extends StyleProps, DOMRenderProps<'thead', undefined>, GlobalDOMAttributes<HTMLTableSectionElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-CalendarGridHeader'
     */
    className?: string;
    /** A function to render a `<CalendarHeaderCell>` for a weekday name. */
    children: (day: string) => ReactElement;
}
/**
 * A calendar grid header displays a row of week day names at the top of a month.
 */
declare const CalendarGridHeaderForwardRef: (props: CalendarGridHeaderProps & React.RefAttributes<HTMLTableSectionElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export { CalendarGridHeaderForwardRef as CalendarGridHeader };
export interface CalendarHeaderCellProps extends DOMProps, DOMRenderProps<'th', undefined>, GlobalDOMAttributes<HTMLTableHeaderCellElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-CalendarHeaderCell'
     */
    className?: string;
}
/**
 * A calendar header cell displays a week day name at the top of a column within a calendar.
 */
declare const CalendarHeaderCellForwardRef: React.ForwardRefExoticComponent<CalendarHeaderCellProps & React.RefAttributes<HTMLTableCellElement>>;
export { CalendarHeaderCellForwardRef as CalendarHeaderCell };
export interface CalendarGridBodyProps extends StyleProps, DOMRenderProps<'tbody', undefined>, GlobalDOMAttributes<HTMLTableSectionElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-CalendarGridBody'
     */
    className?: string;
    /** A function to render a `<CalendarCell>` for a given date. */
    children: (date: CalendarDate) => ReactElement;
}
/**
 * A calendar grid body displays a grid of calendar cells within a month.
 */
declare const CalendarGridBodyForwardRef: (props: CalendarGridBodyProps & React.RefAttributes<HTMLTableSectionElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export { CalendarGridBodyForwardRef as CalendarGridBody };
export interface CalendarCellProps extends RenderProps<CalendarCellRenderProps, 'div'>, HoverEvents, GlobalDOMAttributes<HTMLTableCellElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-CalendarCell'
     */
    className?: ClassNameOrFunction<CalendarCellRenderProps>;
    /** The date to render in the cell. */
    date: CalendarDate;
}
/**
 * A calendar cell displays a date cell within a calendar grid which can be selected by the user.
 */
export declare const CalendarCell: (props: CalendarCellProps & React.RefAttributes<HTMLTableCellElement>) => ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface CalendarYearPickerProps extends AriaCalendarYearPickerProps {
    /**
     * A function to render the year picker.
     */
    children: (renderProps: CalendarYearPickerAria) => JSX.Element;
}
export declare function CalendarYearPicker(props: CalendarYearPickerProps): JSX.Element;
export interface CalendarMonthPickerProps extends AriaCalendarMonthPickerProps {
    /**
     * A function to render the month picker.
     */
    children: (renderProps: CalendarMonthPickerAria) => JSX.Element;
}
export declare function CalendarMonthPicker(props: CalendarMonthPickerProps): JSX.Element;
export interface CalendarHeadingProps extends AriaCalendarHeadingProps, HeadingProps {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-CalendarHeading'
     */
    className?: string;
}
export declare const CalendarHeading: React.ForwardRefExoticComponent<CalendarHeadingProps & React.RefAttributes<HTMLHeadingElement>>;
