import {ButtonContext as $7705c033048f6da7$export$24d547caef80ccd1} from "./Button.mjs";
import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {Heading as $2ec61d1d4f780267$export$a8a3e93435678ff9, HeadingContext as $2ec61d1d4f780267$export$d688439359537581} from "./Heading.mjs";
import {TextContext as $efe09c6d1c304b50$export$9afb8bc826b033ea} from "./Text.mjs";
import {useCalendar as $drly1$useCalendar, useCalendarGrid as $drly1$useCalendarGrid, useCalendarCell as $drly1$useCalendarCell, useCalendarYearPicker as $drly1$useCalendarYearPicker, useCalendarMonthPicker as $drly1$useCalendarMonthPicker, useCalendarHeading as $drly1$useCalendarHeading} from "react-aria/useCalendar";
import {useRangeCalendar as $drly1$useRangeCalendar} from "react-aria/useRangeCalendar";
import {createCalendar as $drly1$createCalendar, endOfMonth as $drly1$endOfMonth, isSameMonth as $drly1$isSameMonth, isToday as $drly1$isToday, isSameDay as $drly1$isSameDay} from "@internationalized/date";
import {useRangeCalendarState as $drly1$useRangeCalendarState} from "react-stately/useRangeCalendarState";
import {filterDOMProps as $drly1$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $drly1$mergeProps} from "react-aria/mergeProps";
import $drly1$react, {createContext as $drly1$createContext, forwardRef as $drly1$forwardRef, useContext as $drly1$useContext, useRef as $drly1$useRef} from "react";
import {useCalendarState as $drly1$useCalendarState} from "react-stately/useCalendarState";
import {useFocusRing as $drly1$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $drly1$useHover} from "react-aria/useHover";
import {useLocale as $drly1$useLocale} from "react-aria/I18nProvider";
import {VisuallyHidden as $drly1$VisuallyHidden} from "react-aria/VisuallyHidden";

/*
 * Copyright 2022 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 















const $6f9a1820b787aac7$export$3b805cea1f178355 = /*#__PURE__*/ (0, $drly1$createContext)(null);
const $6f9a1820b787aac7$export$233dd9682e1ad64b = /*#__PURE__*/ (0, $drly1$createContext)(null);
const $6f9a1820b787aac7$export$9e31dcedda1dadc7 = /*#__PURE__*/ (0, $drly1$createContext)(null);
const $6f9a1820b787aac7$export$5e0fc348c00f87a0 = /*#__PURE__*/ (0, $drly1$createContext)(null);
const $6f9a1820b787aac7$export$e1aef45b828286de = /*#__PURE__*/ (0, $drly1$forwardRef)(function Calendar(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $6f9a1820b787aac7$export$3b805cea1f178355);
    let { locale: locale } = (0, $drly1$useLocale)();
    let state = (0, $drly1$useCalendarState)({
        ...props,
        locale: locale,
        createCalendar: props.createCalendar || (0, $drly1$createCalendar)
    });
    let { calendarProps: calendarProps, prevButtonProps: prevButtonProps, nextButtonProps: nextButtonProps, errorMessageProps: errorMessageProps, title: title } = (0, $drly1$useCalendar)(props, state);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            state: state,
            isDisabled: props.isDisabled || false,
            isInvalid: state.isValueInvalid
        },
        defaultClassName: 'react-aria-Calendar'
    });
    let DOMProps = (0, $drly1$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $drly1$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $drly1$mergeProps)(DOMProps, renderProps, calendarProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": state.isValueInvalid || undefined
    }, /*#__PURE__*/ (0, $drly1$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                (0, $7705c033048f6da7$export$24d547caef80ccd1),
                {
                    slots: {
                        previous: prevButtonProps,
                        next: nextButtonProps
                    }
                }
            ],
            [
                (0, $2ec61d1d4f780267$export$d688439359537581),
                {
                    'aria-hidden': true,
                    level: 2,
                    children: title
                }
            ],
            [
                $6f9a1820b787aac7$export$9e31dcedda1dadc7,
                state
            ],
            [
                $6f9a1820b787aac7$export$3b805cea1f178355,
                props
            ],
            [
                (0, $efe09c6d1c304b50$export$9afb8bc826b033ea),
                {
                    slots: {
                        errorMessage: errorMessageProps
                    }
                }
            ]
        ]
    }, /*#__PURE__*/ (0, $drly1$react).createElement((0, $drly1$VisuallyHidden), null, /*#__PURE__*/ (0, $drly1$react).createElement("h2", null, calendarProps['aria-label'])), renderProps.children, /*#__PURE__*/ (0, $drly1$react).createElement((0, $drly1$VisuallyHidden), null, /*#__PURE__*/ (0, $drly1$react).createElement("button", {
        "aria-label": nextButtonProps['aria-label'],
        disabled: nextButtonProps.isDisabled,
        onClick: ()=>state.focusNextPage(),
        tabIndex: -1
    }))));
});
const $6f9a1820b787aac7$export$a4f5c8b89d277a8d = /*#__PURE__*/ (0, $drly1$forwardRef)(function RangeCalendar(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $6f9a1820b787aac7$export$233dd9682e1ad64b);
    let { locale: locale } = (0, $drly1$useLocale)();
    let state = (0, $drly1$useRangeCalendarState)({
        ...props,
        locale: locale,
        createCalendar: props.createCalendar || (0, $drly1$createCalendar)
    });
    let { calendarProps: calendarProps, prevButtonProps: prevButtonProps, nextButtonProps: nextButtonProps, errorMessageProps: errorMessageProps, title: title } = (0, $drly1$useRangeCalendar)(props, state, ref);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            state: state,
            isDisabled: props.isDisabled || false,
            isInvalid: state.isValueInvalid
        },
        defaultClassName: 'react-aria-RangeCalendar'
    });
    let DOMProps = (0, $drly1$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $drly1$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $drly1$mergeProps)(renderProps, DOMProps, calendarProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": state.isValueInvalid || undefined
    }, /*#__PURE__*/ (0, $drly1$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                (0, $7705c033048f6da7$export$24d547caef80ccd1),
                {
                    slots: {
                        previous: prevButtonProps,
                        next: nextButtonProps
                    }
                }
            ],
            [
                (0, $2ec61d1d4f780267$export$d688439359537581),
                {
                    'aria-hidden': true,
                    level: 2,
                    children: title
                }
            ],
            [
                $6f9a1820b787aac7$export$5e0fc348c00f87a0,
                state
            ],
            [
                $6f9a1820b787aac7$export$233dd9682e1ad64b,
                props
            ],
            [
                (0, $efe09c6d1c304b50$export$9afb8bc826b033ea),
                {
                    slots: {
                        errorMessage: errorMessageProps
                    }
                }
            ]
        ]
    }, /*#__PURE__*/ (0, $drly1$react).createElement((0, $drly1$VisuallyHidden), null, /*#__PURE__*/ (0, $drly1$react).createElement("h2", null, calendarProps['aria-label'])), renderProps.children, /*#__PURE__*/ (0, $drly1$react).createElement((0, $drly1$VisuallyHidden), null, /*#__PURE__*/ (0, $drly1$react).createElement("button", {
        "aria-label": nextButtonProps['aria-label'],
        disabled: nextButtonProps.isDisabled,
        onClick: ()=>state.focusNextPage(),
        tabIndex: -1
    }))));
});
const $6f9a1820b787aac7$var$InternalCalendarGridContext = /*#__PURE__*/ (0, $drly1$createContext)(null);
const $6f9a1820b787aac7$export$5bd780d491cfc46c = /*#__PURE__*/ (0, $drly1$forwardRef)(function CalendarGrid(props, ref) {
    let calendarState = (0, $drly1$useContext)($6f9a1820b787aac7$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $drly1$useContext)($6f9a1820b787aac7$export$5e0fc348c00f87a0);
    let calenderProps = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)($6f9a1820b787aac7$export$3b805cea1f178355);
    let rangeCalenderProps = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)($6f9a1820b787aac7$export$233dd9682e1ad64b);
    let state = calendarState ?? rangeCalendarState;
    let startDate = state.visibleRange.start;
    if (props.offset) startDate = startDate.add(props.offset);
    let firstDayOfWeek = calenderProps?.firstDayOfWeek ?? rangeCalenderProps?.firstDayOfWeek;
    let { gridProps: gridProps, headerProps: headerProps, weekDays: weekDays, weeksInMonth: weeksInMonth } = (0, $drly1$useCalendarGrid)({
        startDate: startDate,
        endDate: (0, $drly1$endOfMonth)(startDate),
        weekdayStyle: props.weekdayStyle,
        firstDayOfWeek: firstDayOfWeek
    }, state);
    let DOMProps = (0, $drly1$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $drly1$react).createElement($6f9a1820b787aac7$var$InternalCalendarGridContext.Provider, {
        value: {
            headerProps: headerProps,
            weekDays: weekDays,
            startDate: startDate,
            weeksInMonth: weeksInMonth
        }
    }, /*#__PURE__*/ (0, $drly1$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).table, {
        render: props.render,
        ...(0, $drly1$mergeProps)(DOMProps, gridProps),
        ref: ref,
        style: props.style,
        cellPadding: 0,
        className: props.className ?? 'react-aria-CalendarGrid'
    }, typeof props.children !== 'function' ? props.children : /*#__PURE__*/ (0, $drly1$react).createElement((0, $drly1$react).Fragment, null, /*#__PURE__*/ (0, $drly1$react).createElement($6f9a1820b787aac7$export$22e2d15eaa4d2377, null, (day)=>/*#__PURE__*/ (0, $drly1$react).createElement($6f9a1820b787aac7$export$ad2135cac3a11b3d, null, day)), /*#__PURE__*/ (0, $drly1$react).createElement($6f9a1820b787aac7$export$e11f8ba65d857bff, null, props.children))));
});
function $6f9a1820b787aac7$var$CalendarGridHeader(props, ref) {
    let { children: children, style: style, className: className } = props;
    let { headerProps: headerProps, weekDays: weekDays } = (0, $drly1$useContext)($6f9a1820b787aac7$var$InternalCalendarGridContext);
    let DOMProps = (0, $drly1$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $drly1$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).thead, {
        render: props.render,
        ...(0, $drly1$mergeProps)(DOMProps, headerProps),
        ref: ref,
        style: style,
        className: className ?? 'react-aria-CalendarGridHeader'
    }, /*#__PURE__*/ (0, $drly1$react).createElement("tr", null, weekDays.map((day, key)=>/*#__PURE__*/ (0, $drly1$react).cloneElement(children(day), {
            key: key
        }))));
}
/**
 * A calendar grid header displays a row of week day names at the top of a month.
 */ const $6f9a1820b787aac7$export$22e2d15eaa4d2377 = /*#__PURE__*/ (0, $drly1$forwardRef)($6f9a1820b787aac7$var$CalendarGridHeader);
function $6f9a1820b787aac7$var$CalendarHeaderCell(props, ref) {
    let { children: children, style: style, className: className } = props;
    let DOMProps = (0, $drly1$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $drly1$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).th, {
        render: props.render,
        ...DOMProps,
        ref: ref,
        style: style,
        className: className || 'react-aria-CalendarHeaderCell'
    }, children);
}
/**
 * A calendar header cell displays a week day name at the top of a column within a calendar.
 */ const $6f9a1820b787aac7$export$ad2135cac3a11b3d = /*#__PURE__*/ (0, $drly1$forwardRef)($6f9a1820b787aac7$var$CalendarHeaderCell);
function $6f9a1820b787aac7$var$CalendarGridBody(props, ref) {
    let { children: children, style: style, className: className } = props;
    let calendarState = (0, $drly1$useContext)($6f9a1820b787aac7$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $drly1$useContext)($6f9a1820b787aac7$export$5e0fc348c00f87a0);
    let state = calendarState ?? rangeCalendarState;
    let { startDate: startDate, weeksInMonth: weeksInMonth } = (0, $drly1$useContext)($6f9a1820b787aac7$var$InternalCalendarGridContext);
    let DOMProps = (0, $drly1$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $drly1$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).tbody, {
        render: props.render,
        ...DOMProps,
        ref: ref,
        style: style,
        className: className ?? 'react-aria-CalendarGridBody'
    }, [
        ...new Array(weeksInMonth).keys()
    ].map((weekIndex)=>/*#__PURE__*/ (0, $drly1$react).createElement("tr", {
            key: weekIndex
        }, state.getDatesInWeek(weekIndex, startDate).map((date, i)=>date ? /*#__PURE__*/ (0, $drly1$react).cloneElement(children(date), {
                key: i
            }) : /*#__PURE__*/ (0, $drly1$react).createElement("td", {
                key: i
            })))));
}
/**
 * A calendar grid body displays a grid of calendar cells within a month.
 */ const $6f9a1820b787aac7$export$e11f8ba65d857bff = /*#__PURE__*/ (0, $drly1$forwardRef)($6f9a1820b787aac7$var$CalendarGridBody);
const $6f9a1820b787aac7$export$5d847498420df57b = /*#__PURE__*/ (0, $drly1$forwardRef)(function CalendarCell({ date: date, ...otherProps }, ref) {
    let calendarState = (0, $drly1$useContext)($6f9a1820b787aac7$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $drly1$useContext)($6f9a1820b787aac7$export$5e0fc348c00f87a0);
    let state = calendarState ?? rangeCalendarState;
    let { startDate: currentMonth } = (0, $drly1$useContext)($6f9a1820b787aac7$var$InternalCalendarGridContext) ?? {
        startDate: state.visibleRange.start
    };
    let isOutsideMonth = state.visibleDuration.days || state.visibleDuration.weeks ? false : !(0, $drly1$isSameMonth)(currentMonth, date);
    let istoday = (0, $drly1$isToday)(date, state.timeZone);
    let buttonRef = (0, $drly1$useRef)(null);
    let { cellProps: cellProps, buttonProps: buttonProps, ...states } = (0, $drly1$useCalendarCell)({
        date: date,
        isOutsideMonth: isOutsideMonth
    }, state, buttonRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $drly1$useHover)({
        ...otherProps,
        isDisabled: states.isDisabled || states.isUnavailable
    });
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $drly1$useFocusRing)();
    isFocusVisible &&= states.isFocused;
    let isSelectionStart = false;
    let isSelectionEnd = false;
    if ('highlightedRange' in state && state.highlightedRange) {
        isSelectionStart = (0, $drly1$isSameDay)(date, state.highlightedRange.start);
        isSelectionEnd = (0, $drly1$isSameDay)(date, state.highlightedRange.end);
    }
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...otherProps,
        defaultChildren: states.formattedDate,
        defaultClassName: 'react-aria-CalendarCell',
        values: {
            date: date,
            isHovered: isHovered,
            isOutsideMonth: isOutsideMonth,
            isFocusVisible: isFocusVisible,
            isSelectionStart: isSelectionStart,
            isSelectionEnd: isSelectionEnd,
            isToday: istoday,
            ...states
        }
    });
    let dataAttrs = {
        'data-focused': states.isFocused || undefined,
        'data-hovered': isHovered || undefined,
        'data-pressed': states.isPressed || undefined,
        'data-unavailable': states.isUnavailable || undefined,
        'data-disabled': states.isDisabled || undefined,
        'data-focus-visible': isFocusVisible || undefined,
        'data-outside-visible-range': states.isOutsideVisibleRange || undefined,
        'data-outside-month': isOutsideMonth || undefined,
        'data-selected': states.isSelected || undefined,
        'data-selection-start': isSelectionStart || undefined,
        'data-selection-end': isSelectionEnd || undefined,
        'data-invalid': states.isInvalid || undefined,
        'data-today': istoday || undefined
    };
    let DOMProps = (0, $drly1$filterDOMProps)(otherProps, {
        global: true
    });
    return /*#__PURE__*/ (0, $drly1$react).createElement("td", {
        ...cellProps,
        ref: ref
    }, /*#__PURE__*/ (0, $drly1$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $drly1$mergeProps)(DOMProps, buttonProps, focusProps, hoverProps, dataAttrs, renderProps),
        ref: buttonRef
    }));
});
function $6f9a1820b787aac7$export$f0ed7ae5d49afb95(props) {
    let calendarState = (0, $drly1$react).useContext($6f9a1820b787aac7$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $drly1$react).useContext($6f9a1820b787aac7$export$5e0fc348c00f87a0);
    let state = calendarState || rangeCalendarState;
    let aria = (0, $drly1$useCalendarYearPicker)(props, state);
    return props.children(aria);
}
function $6f9a1820b787aac7$export$76806186243e9de6(props) {
    let calendarState = (0, $drly1$react).useContext($6f9a1820b787aac7$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $drly1$react).useContext($6f9a1820b787aac7$export$5e0fc348c00f87a0);
    let state = calendarState || rangeCalendarState;
    let aria = (0, $drly1$useCalendarMonthPicker)(props, state);
    return props.children(aria);
}
const $6f9a1820b787aac7$export$77af08ed164baa7 = /*#__PURE__*/ (0, $drly1$forwardRef)(function CalendarHeading(props, ref) {
    let { offset: offset, format: format, className: className = 'react-aria-CalendarHeading', ...headingProps } = props;
    let calendarState = (0, $drly1$react).useContext($6f9a1820b787aac7$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $drly1$react).useContext($6f9a1820b787aac7$export$5e0fc348c00f87a0);
    let state = calendarState || rangeCalendarState;
    let aria = (0, $drly1$useCalendarHeading)({
        offset: offset,
        format: format
    }, state);
    return /*#__PURE__*/ (0, $drly1$react).createElement((0, $2ec61d1d4f780267$export$a8a3e93435678ff9), {
        ...headingProps,
        className: className,
        ref: ref
    }, aria);
});


export {$6f9a1820b787aac7$export$3b805cea1f178355 as CalendarContext, $6f9a1820b787aac7$export$233dd9682e1ad64b as RangeCalendarContext, $6f9a1820b787aac7$export$9e31dcedda1dadc7 as CalendarStateContext, $6f9a1820b787aac7$export$5e0fc348c00f87a0 as RangeCalendarStateContext, $6f9a1820b787aac7$export$e1aef45b828286de as Calendar, $6f9a1820b787aac7$export$a4f5c8b89d277a8d as RangeCalendar, $6f9a1820b787aac7$export$5bd780d491cfc46c as CalendarGrid, $6f9a1820b787aac7$export$22e2d15eaa4d2377 as CalendarGridHeader, $6f9a1820b787aac7$export$ad2135cac3a11b3d as CalendarHeaderCell, $6f9a1820b787aac7$export$e11f8ba65d857bff as CalendarGridBody, $6f9a1820b787aac7$export$5d847498420df57b as CalendarCell, $6f9a1820b787aac7$export$f0ed7ae5d49afb95 as CalendarYearPicker, $6f9a1820b787aac7$export$76806186243e9de6 as CalendarMonthPicker, $6f9a1820b787aac7$export$77af08ed164baa7 as CalendarHeading};
//# sourceMappingURL=Calendar.mjs.map
