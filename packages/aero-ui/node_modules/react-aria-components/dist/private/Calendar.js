import {ButtonContext as $fc203795b9b363cd$export$24d547caef80ccd1} from "./Button.js";
import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3, useSlottedContext as $b7b7a92703138c9b$export$fabf2dc03a41866e} from "./utils.js";
import {Heading as $cd487658cd83358d$export$a8a3e93435678ff9, HeadingContext as $cd487658cd83358d$export$d688439359537581} from "./Heading.js";
import {TextContext as $20d769b1e2b13352$export$9afb8bc826b033ea} from "./Text.js";
import {useCalendar as $T9PfW$useCalendar, useCalendarGrid as $T9PfW$useCalendarGrid, useCalendarCell as $T9PfW$useCalendarCell, useCalendarYearPicker as $T9PfW$useCalendarYearPicker, useCalendarMonthPicker as $T9PfW$useCalendarMonthPicker, useCalendarHeading as $T9PfW$useCalendarHeading} from "react-aria/useCalendar";
import {useRangeCalendar as $T9PfW$useRangeCalendar} from "react-aria/useRangeCalendar";
import {createCalendar as $T9PfW$createCalendar, endOfMonth as $T9PfW$endOfMonth, isSameMonth as $T9PfW$isSameMonth, isToday as $T9PfW$isToday, isSameDay as $T9PfW$isSameDay} from "@internationalized/date";
import {useRangeCalendarState as $T9PfW$useRangeCalendarState} from "react-stately/useRangeCalendarState";
import {filterDOMProps as $T9PfW$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $T9PfW$mergeProps} from "react-aria/mergeProps";
import $T9PfW$react, {createContext as $T9PfW$createContext, forwardRef as $T9PfW$forwardRef, useContext as $T9PfW$useContext, useRef as $T9PfW$useRef} from "react";
import {useCalendarState as $T9PfW$useCalendarState} from "react-stately/useCalendarState";
import {useFocusRing as $T9PfW$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $T9PfW$useHover} from "react-aria/useHover";
import {useLocale as $T9PfW$useLocale} from "react-aria/I18nProvider";
import {VisuallyHidden as $T9PfW$VisuallyHidden} from "react-aria/VisuallyHidden";

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















const $62acebab8283d780$export$3b805cea1f178355 = /*#__PURE__*/ (0, $T9PfW$createContext)(null);
const $62acebab8283d780$export$233dd9682e1ad64b = /*#__PURE__*/ (0, $T9PfW$createContext)(null);
const $62acebab8283d780$export$9e31dcedda1dadc7 = /*#__PURE__*/ (0, $T9PfW$createContext)(null);
const $62acebab8283d780$export$5e0fc348c00f87a0 = /*#__PURE__*/ (0, $T9PfW$createContext)(null);
const $62acebab8283d780$export$e1aef45b828286de = /*#__PURE__*/ (0, $T9PfW$forwardRef)(function Calendar(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $62acebab8283d780$export$3b805cea1f178355);
    let { locale: locale } = (0, $T9PfW$useLocale)();
    let state = (0, $T9PfW$useCalendarState)({
        ...props,
        locale: locale,
        createCalendar: props.createCalendar || (0, $T9PfW$createCalendar)
    });
    let { calendarProps: calendarProps, prevButtonProps: prevButtonProps, nextButtonProps: nextButtonProps, errorMessageProps: errorMessageProps, title: title } = (0, $T9PfW$useCalendar)(props, state);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        values: {
            state: state,
            isDisabled: props.isDisabled || false,
            isInvalid: state.isValueInvalid
        },
        defaultClassName: 'react-aria-Calendar'
    });
    let DOMProps = (0, $T9PfW$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $T9PfW$mergeProps)(DOMProps, renderProps, calendarProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": state.isValueInvalid || undefined
    }, /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                (0, $fc203795b9b363cd$export$24d547caef80ccd1),
                {
                    slots: {
                        previous: prevButtonProps,
                        next: nextButtonProps
                    }
                }
            ],
            [
                (0, $cd487658cd83358d$export$d688439359537581),
                {
                    'aria-hidden': true,
                    level: 2,
                    children: title
                }
            ],
            [
                $62acebab8283d780$export$9e31dcedda1dadc7,
                state
            ],
            [
                $62acebab8283d780$export$3b805cea1f178355,
                props
            ],
            [
                (0, $20d769b1e2b13352$export$9afb8bc826b033ea),
                {
                    slots: {
                        errorMessage: errorMessageProps
                    }
                }
            ]
        ]
    }, /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $T9PfW$VisuallyHidden), null, /*#__PURE__*/ (0, $T9PfW$react).createElement("h2", null, calendarProps['aria-label'])), renderProps.children, /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $T9PfW$VisuallyHidden), null, /*#__PURE__*/ (0, $T9PfW$react).createElement("button", {
        "aria-label": nextButtonProps['aria-label'],
        disabled: nextButtonProps.isDisabled,
        onClick: ()=>state.focusNextPage(),
        tabIndex: -1
    }))));
});
const $62acebab8283d780$export$a4f5c8b89d277a8d = /*#__PURE__*/ (0, $T9PfW$forwardRef)(function RangeCalendar(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $62acebab8283d780$export$233dd9682e1ad64b);
    let { locale: locale } = (0, $T9PfW$useLocale)();
    let state = (0, $T9PfW$useRangeCalendarState)({
        ...props,
        locale: locale,
        createCalendar: props.createCalendar || (0, $T9PfW$createCalendar)
    });
    let { calendarProps: calendarProps, prevButtonProps: prevButtonProps, nextButtonProps: nextButtonProps, errorMessageProps: errorMessageProps, title: title } = (0, $T9PfW$useRangeCalendar)(props, state, ref);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        values: {
            state: state,
            isDisabled: props.isDisabled || false,
            isInvalid: state.isValueInvalid
        },
        defaultClassName: 'react-aria-RangeCalendar'
    });
    let DOMProps = (0, $T9PfW$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $T9PfW$mergeProps)(renderProps, DOMProps, calendarProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": state.isValueInvalid || undefined
    }, /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                (0, $fc203795b9b363cd$export$24d547caef80ccd1),
                {
                    slots: {
                        previous: prevButtonProps,
                        next: nextButtonProps
                    }
                }
            ],
            [
                (0, $cd487658cd83358d$export$d688439359537581),
                {
                    'aria-hidden': true,
                    level: 2,
                    children: title
                }
            ],
            [
                $62acebab8283d780$export$5e0fc348c00f87a0,
                state
            ],
            [
                $62acebab8283d780$export$233dd9682e1ad64b,
                props
            ],
            [
                (0, $20d769b1e2b13352$export$9afb8bc826b033ea),
                {
                    slots: {
                        errorMessage: errorMessageProps
                    }
                }
            ]
        ]
    }, /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $T9PfW$VisuallyHidden), null, /*#__PURE__*/ (0, $T9PfW$react).createElement("h2", null, calendarProps['aria-label'])), renderProps.children, /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $T9PfW$VisuallyHidden), null, /*#__PURE__*/ (0, $T9PfW$react).createElement("button", {
        "aria-label": nextButtonProps['aria-label'],
        disabled: nextButtonProps.isDisabled,
        onClick: ()=>state.focusNextPage(),
        tabIndex: -1
    }))));
});
const $62acebab8283d780$var$InternalCalendarGridContext = /*#__PURE__*/ (0, $T9PfW$createContext)(null);
const $62acebab8283d780$export$5bd780d491cfc46c = /*#__PURE__*/ (0, $T9PfW$forwardRef)(function CalendarGrid(props, ref) {
    let calendarState = (0, $T9PfW$useContext)($62acebab8283d780$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $T9PfW$useContext)($62acebab8283d780$export$5e0fc348c00f87a0);
    let calenderProps = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)($62acebab8283d780$export$3b805cea1f178355);
    let rangeCalenderProps = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)($62acebab8283d780$export$233dd9682e1ad64b);
    let state = calendarState !== null && calendarState !== void 0 ? calendarState : rangeCalendarState;
    let startDate = state.visibleRange.start;
    if (props.offset) startDate = startDate.add(props.offset);
    var _calenderProps_firstDayOfWeek;
    let firstDayOfWeek = (_calenderProps_firstDayOfWeek = calenderProps === null || calenderProps === void 0 ? void 0 : calenderProps.firstDayOfWeek) !== null && _calenderProps_firstDayOfWeek !== void 0 ? _calenderProps_firstDayOfWeek : rangeCalenderProps === null || rangeCalenderProps === void 0 ? void 0 : rangeCalenderProps.firstDayOfWeek;
    let { gridProps: gridProps, headerProps: headerProps, weekDays: weekDays, weeksInMonth: weeksInMonth } = (0, $T9PfW$useCalendarGrid)({
        startDate: startDate,
        endDate: (0, $T9PfW$endOfMonth)(startDate),
        weekdayStyle: props.weekdayStyle,
        firstDayOfWeek: firstDayOfWeek
    }, state);
    let DOMProps = (0, $T9PfW$filterDOMProps)(props, {
        global: true
    });
    var _props_className;
    return /*#__PURE__*/ (0, $T9PfW$react).createElement($62acebab8283d780$var$InternalCalendarGridContext.Provider, {
        value: {
            headerProps: headerProps,
            weekDays: weekDays,
            startDate: startDate,
            weeksInMonth: weeksInMonth
        }
    }, /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).table, {
        render: props.render,
        ...(0, $T9PfW$mergeProps)(DOMProps, gridProps),
        ref: ref,
        style: props.style,
        cellPadding: 0,
        className: (_props_className = props.className) !== null && _props_className !== void 0 ? _props_className : 'react-aria-CalendarGrid'
    }, typeof props.children !== 'function' ? props.children : /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $T9PfW$react).Fragment, null, /*#__PURE__*/ (0, $T9PfW$react).createElement($62acebab8283d780$export$22e2d15eaa4d2377, null, (day)=>/*#__PURE__*/ (0, $T9PfW$react).createElement($62acebab8283d780$export$ad2135cac3a11b3d, null, day)), /*#__PURE__*/ (0, $T9PfW$react).createElement($62acebab8283d780$export$e11f8ba65d857bff, null, props.children))));
});
function $62acebab8283d780$var$CalendarGridHeader(props, ref) {
    let { children: children, style: style, className: className } = props;
    let { headerProps: headerProps, weekDays: weekDays } = (0, $T9PfW$useContext)($62acebab8283d780$var$InternalCalendarGridContext);
    let DOMProps = (0, $T9PfW$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).thead, {
        render: props.render,
        ...(0, $T9PfW$mergeProps)(DOMProps, headerProps),
        ref: ref,
        style: style,
        className: className !== null && className !== void 0 ? className : 'react-aria-CalendarGridHeader'
    }, /*#__PURE__*/ (0, $T9PfW$react).createElement("tr", null, weekDays.map((day, key)=>/*#__PURE__*/ (0, $T9PfW$react).cloneElement(children(day), {
            key: key
        }))));
}
/**
 * A calendar grid header displays a row of week day names at the top of a month.
 */ const $62acebab8283d780$export$22e2d15eaa4d2377 = /*#__PURE__*/ (0, $T9PfW$forwardRef)($62acebab8283d780$var$CalendarGridHeader);
function $62acebab8283d780$var$CalendarHeaderCell(props, ref) {
    let { children: children, style: style, className: className } = props;
    let DOMProps = (0, $T9PfW$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).th, {
        render: props.render,
        ...DOMProps,
        ref: ref,
        style: style,
        className: className || 'react-aria-CalendarHeaderCell'
    }, children);
}
/**
 * A calendar header cell displays a week day name at the top of a column within a calendar.
 */ const $62acebab8283d780$export$ad2135cac3a11b3d = /*#__PURE__*/ (0, $T9PfW$forwardRef)($62acebab8283d780$var$CalendarHeaderCell);
function $62acebab8283d780$var$CalendarGridBody(props, ref) {
    let { children: children, style: style, className: className } = props;
    let calendarState = (0, $T9PfW$useContext)($62acebab8283d780$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $T9PfW$useContext)($62acebab8283d780$export$5e0fc348c00f87a0);
    let state = calendarState !== null && calendarState !== void 0 ? calendarState : rangeCalendarState;
    let { startDate: startDate, weeksInMonth: weeksInMonth } = (0, $T9PfW$useContext)($62acebab8283d780$var$InternalCalendarGridContext);
    let DOMProps = (0, $T9PfW$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).tbody, {
        render: props.render,
        ...DOMProps,
        ref: ref,
        style: style,
        className: className !== null && className !== void 0 ? className : 'react-aria-CalendarGridBody'
    }, [
        ...new Array(weeksInMonth).keys()
    ].map((weekIndex)=>/*#__PURE__*/ (0, $T9PfW$react).createElement("tr", {
            key: weekIndex
        }, state.getDatesInWeek(weekIndex, startDate).map((date, i)=>date ? /*#__PURE__*/ (0, $T9PfW$react).cloneElement(children(date), {
                key: i
            }) : /*#__PURE__*/ (0, $T9PfW$react).createElement("td", {
                key: i
            })))));
}
/**
 * A calendar grid body displays a grid of calendar cells within a month.
 */ const $62acebab8283d780$export$e11f8ba65d857bff = /*#__PURE__*/ (0, $T9PfW$forwardRef)($62acebab8283d780$var$CalendarGridBody);
const $62acebab8283d780$export$5d847498420df57b = /*#__PURE__*/ (0, $T9PfW$forwardRef)(function CalendarCell({ date: date, ...otherProps }, ref) {
    let calendarState = (0, $T9PfW$useContext)($62acebab8283d780$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $T9PfW$useContext)($62acebab8283d780$export$5e0fc348c00f87a0);
    let state = calendarState !== null && calendarState !== void 0 ? calendarState : rangeCalendarState;
    var _useContext;
    let { startDate: currentMonth } = (_useContext = (0, $T9PfW$useContext)($62acebab8283d780$var$InternalCalendarGridContext)) !== null && _useContext !== void 0 ? _useContext : {
        startDate: state.visibleRange.start
    };
    let isOutsideMonth = state.visibleDuration.days || state.visibleDuration.weeks ? false : !(0, $T9PfW$isSameMonth)(currentMonth, date);
    let istoday = (0, $T9PfW$isToday)(date, state.timeZone);
    let buttonRef = (0, $T9PfW$useRef)(null);
    let { cellProps: cellProps, buttonProps: buttonProps, ...states } = (0, $T9PfW$useCalendarCell)({
        date: date,
        isOutsideMonth: isOutsideMonth
    }, state, buttonRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $T9PfW$useHover)({
        ...otherProps,
        isDisabled: states.isDisabled || states.isUnavailable
    });
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $T9PfW$useFocusRing)();
    isFocusVisible && (isFocusVisible = states.isFocused);
    let isSelectionStart = false;
    let isSelectionEnd = false;
    if ('highlightedRange' in state && state.highlightedRange) {
        isSelectionStart = (0, $T9PfW$isSameDay)(date, state.highlightedRange.start);
        isSelectionEnd = (0, $T9PfW$isSameDay)(date, state.highlightedRange.end);
    }
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $T9PfW$filterDOMProps)(otherProps, {
        global: true
    });
    return /*#__PURE__*/ (0, $T9PfW$react).createElement("td", {
        ...cellProps,
        ref: ref
    }, /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $T9PfW$mergeProps)(DOMProps, buttonProps, focusProps, hoverProps, dataAttrs, renderProps),
        ref: buttonRef
    }));
});
function $62acebab8283d780$export$f0ed7ae5d49afb95(props) {
    let calendarState = (0, $T9PfW$react).useContext($62acebab8283d780$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $T9PfW$react).useContext($62acebab8283d780$export$5e0fc348c00f87a0);
    let state = calendarState || rangeCalendarState;
    let aria = (0, $T9PfW$useCalendarYearPicker)(props, state);
    return props.children(aria);
}
function $62acebab8283d780$export$76806186243e9de6(props) {
    let calendarState = (0, $T9PfW$react).useContext($62acebab8283d780$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $T9PfW$react).useContext($62acebab8283d780$export$5e0fc348c00f87a0);
    let state = calendarState || rangeCalendarState;
    let aria = (0, $T9PfW$useCalendarMonthPicker)(props, state);
    return props.children(aria);
}
const $62acebab8283d780$export$77af08ed164baa7 = /*#__PURE__*/ (0, $T9PfW$forwardRef)(function CalendarHeading(props, ref) {
    let { offset: offset, format: format, className: className = 'react-aria-CalendarHeading', ...headingProps } = props;
    let calendarState = (0, $T9PfW$react).useContext($62acebab8283d780$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $T9PfW$react).useContext($62acebab8283d780$export$5e0fc348c00f87a0);
    let state = calendarState || rangeCalendarState;
    let aria = (0, $T9PfW$useCalendarHeading)({
        offset: offset,
        format: format
    }, state);
    return /*#__PURE__*/ (0, $T9PfW$react).createElement((0, $cd487658cd83358d$export$a8a3e93435678ff9), {
        ...headingProps,
        className: className,
        ref: ref
    }, aria);
});


export {$62acebab8283d780$export$3b805cea1f178355 as CalendarContext, $62acebab8283d780$export$233dd9682e1ad64b as RangeCalendarContext, $62acebab8283d780$export$9e31dcedda1dadc7 as CalendarStateContext, $62acebab8283d780$export$5e0fc348c00f87a0 as RangeCalendarStateContext, $62acebab8283d780$export$e1aef45b828286de as Calendar, $62acebab8283d780$export$a4f5c8b89d277a8d as RangeCalendar, $62acebab8283d780$export$5bd780d491cfc46c as CalendarGrid, $62acebab8283d780$export$22e2d15eaa4d2377 as CalendarGridHeader, $62acebab8283d780$export$ad2135cac3a11b3d as CalendarHeaderCell, $62acebab8283d780$export$e11f8ba65d857bff as CalendarGridBody, $62acebab8283d780$export$5d847498420df57b as CalendarCell, $62acebab8283d780$export$f0ed7ae5d49afb95 as CalendarYearPicker, $62acebab8283d780$export$76806186243e9de6 as CalendarMonthPicker, $62acebab8283d780$export$77af08ed164baa7 as CalendarHeading};
//# sourceMappingURL=Calendar.js.map
