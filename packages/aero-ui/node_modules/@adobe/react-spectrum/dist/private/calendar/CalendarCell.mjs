import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../calendar_vars.css";
import $2R6SN$calendar_vars_cssmjs from "../calendar_vars_css.mjs";
import {useCalendarCell as $2R6SN$useCalendarCell} from "react-aria/useCalendar";
import {isSameMonth as $2R6SN$isSameMonth, isSameDay as $2R6SN$isSameDay, getDayOfWeek as $2R6SN$getDayOfWeek, isToday as $2R6SN$isToday} from "@internationalized/date";
import {mergeProps as $2R6SN$mergeProps} from "react-aria/mergeProps";
import $2R6SN$react, {useRef as $2R6SN$useRef} from "react";
import {useFocusRing as $2R6SN$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $2R6SN$useHover} from "react-aria/useHover";
import {useLocale as $2R6SN$useLocale} from "react-aria/I18nProvider";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
/*
 * Copyright 2020 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 








function $e70370a79212983d$export$5d847498420df57b({ state: state, currentMonth: currentMonth, firstDayOfWeek: firstDayOfWeek, ...props }) {
    let ref = (0, $2R6SN$useRef)(null);
    let { cellProps: cellProps, buttonProps: buttonProps, isPressed: isPressed, isSelected: isSelected, isDisabled: isDisabled, isFocused: isFocused, isInvalid: isInvalid, formattedDate: formattedDate } = (0, $2R6SN$useCalendarCell)({
        ...props,
        isDisabled: !(0, $2R6SN$isSameMonth)(props.date, currentMonth)
    }, state, ref);
    let isUnavailable = state.isCellUnavailable(props.date) && !isDisabled;
    let isLastSelectedBeforeDisabled = !isDisabled && !isInvalid && state.isCellUnavailable(props.date.add({
        days: 1
    }));
    let isFirstSelectedAfterDisabled = !isDisabled && !isInvalid && state.isCellUnavailable(props.date.subtract({
        days: 1
    }));
    let highlightedRange = 'highlightedRange' in state && state.highlightedRange;
    let isSelectionStart = isSelected && highlightedRange && (0, $2R6SN$isSameDay)(props.date, highlightedRange.start);
    let isSelectionEnd = isSelected && highlightedRange && (0, $2R6SN$isSameDay)(props.date, highlightedRange.end);
    let { locale: locale } = (0, $2R6SN$useLocale)();
    let dayOfWeek = (0, $2R6SN$getDayOfWeek)(props.date, locale, firstDayOfWeek);
    let isRangeStart = isSelected && (isFirstSelectedAfterDisabled || dayOfWeek === 0 || props.date.day === 1);
    let isRangeEnd = isSelected && (isLastSelectedBeforeDisabled || dayOfWeek === 6 || props.date.day === currentMonth.calendar.getDaysInMonth(currentMonth));
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $2R6SN$useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $2R6SN$useHover)({
        isDisabled: isDisabled || isUnavailable || state.isReadOnly
    });
    return /*#__PURE__*/ (0, $2R6SN$react).createElement("td", {
        ...cellProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2R6SN$calendar_vars_cssmjs))), 'spectrum-Calendar-tableCell')
    }, /*#__PURE__*/ (0, $2R6SN$react).createElement("span", {
        ...(0, $2R6SN$mergeProps)(buttonProps, hoverProps, focusProps),
        ref: ref,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2R6SN$calendar_vars_cssmjs))), 'spectrum-Calendar-date', {
            'is-today': (0, $2R6SN$isToday)(props.date, state.timeZone),
            'is-selected': isSelected,
            'is-focused': isFocused && isFocusVisible,
            // Style disabled (i.e. out of min/max range), but selected dates as unavailable
            // since it is more clear than trying to dim the selection.
            'is-disabled': isDisabled && !isInvalid,
            'is-unavailable': isUnavailable || isInvalid && isDisabled,
            'is-outsideMonth': !(0, $2R6SN$isSameMonth)(props.date, currentMonth),
            'is-range-start': isRangeStart,
            'is-range-end': isRangeEnd,
            'is-range-selection': isSelected && 'highlightedRange' in state,
            'is-selection-start': isSelectionStart,
            'is-selection-end': isSelectionEnd,
            'is-hovered': isHovered,
            'is-pressed': isPressed && !state.isReadOnly,
            'is-invalid': isInvalid
        })
    }, /*#__PURE__*/ (0, $2R6SN$react).createElement("span", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2R6SN$calendar_vars_cssmjs))), 'spectrum-Calendar-dateText')
    }, /*#__PURE__*/ (0, $2R6SN$react).createElement("span", null, formattedDate))));
}


export {$e70370a79212983d$export$5d847498420df57b as CalendarCell};
//# sourceMappingURL=CalendarCell.mjs.map
