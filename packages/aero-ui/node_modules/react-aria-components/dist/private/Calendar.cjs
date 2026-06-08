var $16c7f9b22cce3838$exports = require("./Button.cjs");
var $048d76b84370f141$exports = require("./utils.cjs");
var $03e8b4fd5e44cde9$exports = require("./Heading.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $ewi3q$reactariauseCalendar = require("react-aria/useCalendar");
var $ewi3q$reactariauseRangeCalendar = require("react-aria/useRangeCalendar");
var $ewi3q$internationalizeddate = require("@internationalized/date");
var $ewi3q$reactstatelyuseRangeCalendarState = require("react-stately/useRangeCalendarState");
var $ewi3q$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $ewi3q$reactariamergeProps = require("react-aria/mergeProps");
var $ewi3q$react = require("react");
var $ewi3q$reactstatelyuseCalendarState = require("react-stately/useCalendarState");
var $ewi3q$reactariauseFocusRing = require("react-aria/useFocusRing");
var $ewi3q$reactariauseHover = require("react-aria/useHover");
var $ewi3q$reactariaI18nProvider = require("react-aria/I18nProvider");
var $ewi3q$reactariaVisuallyHidden = require("react-aria/VisuallyHidden");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "CalendarContext", function () { return $27a812393b1f8a86$export$3b805cea1f178355; });
$parcel$export(module.exports, "RangeCalendarContext", function () { return $27a812393b1f8a86$export$233dd9682e1ad64b; });
$parcel$export(module.exports, "CalendarStateContext", function () { return $27a812393b1f8a86$export$9e31dcedda1dadc7; });
$parcel$export(module.exports, "RangeCalendarStateContext", function () { return $27a812393b1f8a86$export$5e0fc348c00f87a0; });
$parcel$export(module.exports, "Calendar", function () { return $27a812393b1f8a86$export$e1aef45b828286de; });
$parcel$export(module.exports, "RangeCalendar", function () { return $27a812393b1f8a86$export$a4f5c8b89d277a8d; });
$parcel$export(module.exports, "CalendarGrid", function () { return $27a812393b1f8a86$export$5bd780d491cfc46c; });
$parcel$export(module.exports, "CalendarGridHeader", function () { return $27a812393b1f8a86$export$22e2d15eaa4d2377; });
$parcel$export(module.exports, "CalendarHeaderCell", function () { return $27a812393b1f8a86$export$ad2135cac3a11b3d; });
$parcel$export(module.exports, "CalendarGridBody", function () { return $27a812393b1f8a86$export$e11f8ba65d857bff; });
$parcel$export(module.exports, "CalendarCell", function () { return $27a812393b1f8a86$export$5d847498420df57b; });
$parcel$export(module.exports, "CalendarYearPicker", function () { return $27a812393b1f8a86$export$f0ed7ae5d49afb95; });
$parcel$export(module.exports, "CalendarMonthPicker", function () { return $27a812393b1f8a86$export$76806186243e9de6; });
$parcel$export(module.exports, "CalendarHeading", function () { return $27a812393b1f8a86$export$77af08ed164baa7; });
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















const $27a812393b1f8a86$export$3b805cea1f178355 = /*#__PURE__*/ (0, $ewi3q$react.createContext)(null);
const $27a812393b1f8a86$export$233dd9682e1ad64b = /*#__PURE__*/ (0, $ewi3q$react.createContext)(null);
const $27a812393b1f8a86$export$9e31dcedda1dadc7 = /*#__PURE__*/ (0, $ewi3q$react.createContext)(null);
const $27a812393b1f8a86$export$5e0fc348c00f87a0 = /*#__PURE__*/ (0, $ewi3q$react.createContext)(null);
const $27a812393b1f8a86$export$e1aef45b828286de = /*#__PURE__*/ (0, $ewi3q$react.forwardRef)(function Calendar(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $27a812393b1f8a86$export$3b805cea1f178355);
    let { locale: locale } = (0, $ewi3q$reactariaI18nProvider.useLocale)();
    let state = (0, $ewi3q$reactstatelyuseCalendarState.useCalendarState)({
        ...props,
        locale: locale,
        createCalendar: props.createCalendar || (0, $ewi3q$internationalizeddate.createCalendar)
    });
    let { calendarProps: calendarProps, prevButtonProps: prevButtonProps, nextButtonProps: nextButtonProps, errorMessageProps: errorMessageProps, title: title } = (0, $ewi3q$reactariauseCalendar.useCalendar)(props, state);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: {
            state: state,
            isDisabled: props.isDisabled || false,
            isInvalid: state.isValueInvalid
        },
        defaultClassName: 'react-aria-Calendar'
    });
    let DOMProps = (0, $ewi3q$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $ewi3q$reactariamergeProps.mergeProps)(DOMProps, renderProps, calendarProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": state.isValueInvalid || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                (0, $16c7f9b22cce3838$exports.ButtonContext),
                {
                    slots: {
                        previous: prevButtonProps,
                        next: nextButtonProps
                    }
                }
            ],
            [
                (0, $03e8b4fd5e44cde9$exports.HeadingContext),
                {
                    'aria-hidden': true,
                    level: 2,
                    children: title
                }
            ],
            [
                $27a812393b1f8a86$export$9e31dcedda1dadc7,
                state
            ],
            [
                $27a812393b1f8a86$export$3b805cea1f178355,
                props
            ],
            [
                (0, $cab7d9a238d19c33$exports.TextContext),
                {
                    slots: {
                        errorMessage: errorMessageProps
                    }
                }
            ]
        ]
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $ewi3q$reactariaVisuallyHidden.VisuallyHidden), null, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement("h2", null, calendarProps['aria-label'])), renderProps.children, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $ewi3q$reactariaVisuallyHidden.VisuallyHidden), null, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement("button", {
        "aria-label": nextButtonProps['aria-label'],
        disabled: nextButtonProps.isDisabled,
        onClick: ()=>state.focusNextPage(),
        tabIndex: -1
    }))));
});
const $27a812393b1f8a86$export$a4f5c8b89d277a8d = /*#__PURE__*/ (0, $ewi3q$react.forwardRef)(function RangeCalendar(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $27a812393b1f8a86$export$233dd9682e1ad64b);
    let { locale: locale } = (0, $ewi3q$reactariaI18nProvider.useLocale)();
    let state = (0, $ewi3q$reactstatelyuseRangeCalendarState.useRangeCalendarState)({
        ...props,
        locale: locale,
        createCalendar: props.createCalendar || (0, $ewi3q$internationalizeddate.createCalendar)
    });
    let { calendarProps: calendarProps, prevButtonProps: prevButtonProps, nextButtonProps: nextButtonProps, errorMessageProps: errorMessageProps, title: title } = (0, $ewi3q$reactariauseRangeCalendar.useRangeCalendar)(props, state, ref);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: {
            state: state,
            isDisabled: props.isDisabled || false,
            isInvalid: state.isValueInvalid
        },
        defaultClassName: 'react-aria-RangeCalendar'
    });
    let DOMProps = (0, $ewi3q$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $ewi3q$reactariamergeProps.mergeProps)(renderProps, DOMProps, calendarProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": state.isValueInvalid || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                (0, $16c7f9b22cce3838$exports.ButtonContext),
                {
                    slots: {
                        previous: prevButtonProps,
                        next: nextButtonProps
                    }
                }
            ],
            [
                (0, $03e8b4fd5e44cde9$exports.HeadingContext),
                {
                    'aria-hidden': true,
                    level: 2,
                    children: title
                }
            ],
            [
                $27a812393b1f8a86$export$5e0fc348c00f87a0,
                state
            ],
            [
                $27a812393b1f8a86$export$233dd9682e1ad64b,
                props
            ],
            [
                (0, $cab7d9a238d19c33$exports.TextContext),
                {
                    slots: {
                        errorMessage: errorMessageProps
                    }
                }
            ]
        ]
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $ewi3q$reactariaVisuallyHidden.VisuallyHidden), null, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement("h2", null, calendarProps['aria-label'])), renderProps.children, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $ewi3q$reactariaVisuallyHidden.VisuallyHidden), null, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement("button", {
        "aria-label": nextButtonProps['aria-label'],
        disabled: nextButtonProps.isDisabled,
        onClick: ()=>state.focusNextPage(),
        tabIndex: -1
    }))));
});
const $27a812393b1f8a86$var$InternalCalendarGridContext = /*#__PURE__*/ (0, $ewi3q$react.createContext)(null);
const $27a812393b1f8a86$export$5bd780d491cfc46c = /*#__PURE__*/ (0, $ewi3q$react.forwardRef)(function CalendarGrid(props, ref) {
    let calendarState = (0, $ewi3q$react.useContext)($27a812393b1f8a86$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $ewi3q$react.useContext)($27a812393b1f8a86$export$5e0fc348c00f87a0);
    let calenderProps = (0, $048d76b84370f141$exports.useSlottedContext)($27a812393b1f8a86$export$3b805cea1f178355);
    let rangeCalenderProps = (0, $048d76b84370f141$exports.useSlottedContext)($27a812393b1f8a86$export$233dd9682e1ad64b);
    let state = calendarState ?? rangeCalendarState;
    let startDate = state.visibleRange.start;
    if (props.offset) startDate = startDate.add(props.offset);
    let firstDayOfWeek = calenderProps?.firstDayOfWeek ?? rangeCalenderProps?.firstDayOfWeek;
    let { gridProps: gridProps, headerProps: headerProps, weekDays: weekDays, weeksInMonth: weeksInMonth } = (0, $ewi3q$reactariauseCalendar.useCalendarGrid)({
        startDate: startDate,
        endDate: (0, $ewi3q$internationalizeddate.endOfMonth)(startDate),
        weekdayStyle: props.weekdayStyle,
        firstDayOfWeek: firstDayOfWeek
    }, state);
    let DOMProps = (0, $ewi3q$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement($27a812393b1f8a86$var$InternalCalendarGridContext.Provider, {
        value: {
            headerProps: headerProps,
            weekDays: weekDays,
            startDate: startDate,
            weeksInMonth: weeksInMonth
        }
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $048d76b84370f141$exports.dom).table, {
        render: props.render,
        ...(0, $ewi3q$reactariamergeProps.mergeProps)(DOMProps, gridProps),
        ref: ref,
        style: props.style,
        cellPadding: 0,
        className: props.className ?? 'react-aria-CalendarGrid'
    }, typeof props.children !== 'function' ? props.children : /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, ($parcel$interopDefault($ewi3q$react))).Fragment, null, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement($27a812393b1f8a86$export$22e2d15eaa4d2377, null, (day)=>/*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement($27a812393b1f8a86$export$ad2135cac3a11b3d, null, day)), /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement($27a812393b1f8a86$export$e11f8ba65d857bff, null, props.children))));
});
function $27a812393b1f8a86$var$CalendarGridHeader(props, ref) {
    let { children: children, style: style, className: className } = props;
    let { headerProps: headerProps, weekDays: weekDays } = (0, $ewi3q$react.useContext)($27a812393b1f8a86$var$InternalCalendarGridContext);
    let DOMProps = (0, $ewi3q$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $048d76b84370f141$exports.dom).thead, {
        render: props.render,
        ...(0, $ewi3q$reactariamergeProps.mergeProps)(DOMProps, headerProps),
        ref: ref,
        style: style,
        className: className ?? 'react-aria-CalendarGridHeader'
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement("tr", null, weekDays.map((day, key)=>/*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).cloneElement(children(day), {
            key: key
        }))));
}
/**
 * A calendar grid header displays a row of week day names at the top of a month.
 */ const $27a812393b1f8a86$export$22e2d15eaa4d2377 = /*#__PURE__*/ (0, $ewi3q$react.forwardRef)($27a812393b1f8a86$var$CalendarGridHeader);
function $27a812393b1f8a86$var$CalendarHeaderCell(props, ref) {
    let { children: children, style: style, className: className } = props;
    let DOMProps = (0, $ewi3q$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $048d76b84370f141$exports.dom).th, {
        render: props.render,
        ...DOMProps,
        ref: ref,
        style: style,
        className: className || 'react-aria-CalendarHeaderCell'
    }, children);
}
/**
 * A calendar header cell displays a week day name at the top of a column within a calendar.
 */ const $27a812393b1f8a86$export$ad2135cac3a11b3d = /*#__PURE__*/ (0, $ewi3q$react.forwardRef)($27a812393b1f8a86$var$CalendarHeaderCell);
function $27a812393b1f8a86$var$CalendarGridBody(props, ref) {
    let { children: children, style: style, className: className } = props;
    let calendarState = (0, $ewi3q$react.useContext)($27a812393b1f8a86$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $ewi3q$react.useContext)($27a812393b1f8a86$export$5e0fc348c00f87a0);
    let state = calendarState ?? rangeCalendarState;
    let { startDate: startDate, weeksInMonth: weeksInMonth } = (0, $ewi3q$react.useContext)($27a812393b1f8a86$var$InternalCalendarGridContext);
    let DOMProps = (0, $ewi3q$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $048d76b84370f141$exports.dom).tbody, {
        render: props.render,
        ...DOMProps,
        ref: ref,
        style: style,
        className: className ?? 'react-aria-CalendarGridBody'
    }, [
        ...new Array(weeksInMonth).keys()
    ].map((weekIndex)=>/*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement("tr", {
            key: weekIndex
        }, state.getDatesInWeek(weekIndex, startDate).map((date, i)=>date ? /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).cloneElement(children(date), {
                key: i
            }) : /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement("td", {
                key: i
            })))));
}
/**
 * A calendar grid body displays a grid of calendar cells within a month.
 */ const $27a812393b1f8a86$export$e11f8ba65d857bff = /*#__PURE__*/ (0, $ewi3q$react.forwardRef)($27a812393b1f8a86$var$CalendarGridBody);
const $27a812393b1f8a86$export$5d847498420df57b = /*#__PURE__*/ (0, $ewi3q$react.forwardRef)(function CalendarCell({ date: date, ...otherProps }, ref) {
    let calendarState = (0, $ewi3q$react.useContext)($27a812393b1f8a86$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, $ewi3q$react.useContext)($27a812393b1f8a86$export$5e0fc348c00f87a0);
    let state = calendarState ?? rangeCalendarState;
    let { startDate: currentMonth } = (0, $ewi3q$react.useContext)($27a812393b1f8a86$var$InternalCalendarGridContext) ?? {
        startDate: state.visibleRange.start
    };
    let isOutsideMonth = state.visibleDuration.days || state.visibleDuration.weeks ? false : !(0, $ewi3q$internationalizeddate.isSameMonth)(currentMonth, date);
    let istoday = (0, $ewi3q$internationalizeddate.isToday)(date, state.timeZone);
    let buttonRef = (0, $ewi3q$react.useRef)(null);
    let { cellProps: cellProps, buttonProps: buttonProps, ...states } = (0, $ewi3q$reactariauseCalendar.useCalendarCell)({
        date: date,
        isOutsideMonth: isOutsideMonth
    }, state, buttonRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $ewi3q$reactariauseHover.useHover)({
        ...otherProps,
        isDisabled: states.isDisabled || states.isUnavailable
    });
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $ewi3q$reactariauseFocusRing.useFocusRing)();
    isFocusVisible &&= states.isFocused;
    let isSelectionStart = false;
    let isSelectionEnd = false;
    if ('highlightedRange' in state && state.highlightedRange) {
        isSelectionStart = (0, $ewi3q$internationalizeddate.isSameDay)(date, state.highlightedRange.start);
        isSelectionEnd = (0, $ewi3q$internationalizeddate.isSameDay)(date, state.highlightedRange.end);
    }
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $ewi3q$reactariafilterDOMProps.filterDOMProps)(otherProps, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement("td", {
        ...cellProps,
        ref: ref
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $ewi3q$reactariamergeProps.mergeProps)(DOMProps, buttonProps, focusProps, hoverProps, dataAttrs, renderProps),
        ref: buttonRef
    }));
});
function $27a812393b1f8a86$export$f0ed7ae5d49afb95(props) {
    let calendarState = (0, ($parcel$interopDefault($ewi3q$react))).useContext($27a812393b1f8a86$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, ($parcel$interopDefault($ewi3q$react))).useContext($27a812393b1f8a86$export$5e0fc348c00f87a0);
    let state = calendarState || rangeCalendarState;
    let aria = (0, $ewi3q$reactariauseCalendar.useCalendarYearPicker)(props, state);
    return props.children(aria);
}
function $27a812393b1f8a86$export$76806186243e9de6(props) {
    let calendarState = (0, ($parcel$interopDefault($ewi3q$react))).useContext($27a812393b1f8a86$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, ($parcel$interopDefault($ewi3q$react))).useContext($27a812393b1f8a86$export$5e0fc348c00f87a0);
    let state = calendarState || rangeCalendarState;
    let aria = (0, $ewi3q$reactariauseCalendar.useCalendarMonthPicker)(props, state);
    return props.children(aria);
}
const $27a812393b1f8a86$export$77af08ed164baa7 = /*#__PURE__*/ (0, $ewi3q$react.forwardRef)(function CalendarHeading(props, ref) {
    let { offset: offset, format: format, className: className = 'react-aria-CalendarHeading', ...headingProps } = props;
    let calendarState = (0, ($parcel$interopDefault($ewi3q$react))).useContext($27a812393b1f8a86$export$9e31dcedda1dadc7);
    let rangeCalendarState = (0, ($parcel$interopDefault($ewi3q$react))).useContext($27a812393b1f8a86$export$5e0fc348c00f87a0);
    let state = calendarState || rangeCalendarState;
    let aria = (0, $ewi3q$reactariauseCalendar.useCalendarHeading)({
        offset: offset,
        format: format
    }, state);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($ewi3q$react))).createElement((0, $03e8b4fd5e44cde9$exports.Heading), {
        ...headingProps,
        className: className,
        ref: ref
    }, aria);
});


//# sourceMappingURL=Calendar.cjs.map
