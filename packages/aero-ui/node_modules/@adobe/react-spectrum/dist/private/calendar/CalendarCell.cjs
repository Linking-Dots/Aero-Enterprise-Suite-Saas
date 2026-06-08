var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../calendar_vars.css");
var $7671a6feef2ac7d1$exports = require("../calendar_vars_css.cjs");
var $kBAs3$reactariauseCalendar = require("react-aria/useCalendar");
var $kBAs3$internationalizeddate = require("@internationalized/date");
var $kBAs3$reactariamergeProps = require("react-aria/mergeProps");
var $kBAs3$react = require("react");
var $kBAs3$reactariauseFocusRing = require("react-aria/useFocusRing");
var $kBAs3$reactariauseHover = require("react-aria/useHover");
var $kBAs3$reactariaI18nProvider = require("react-aria/I18nProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "CalendarCell", function () { return $9e60f7064368183d$export$5d847498420df57b; });
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








function $9e60f7064368183d$export$5d847498420df57b({ state: state, currentMonth: currentMonth, firstDayOfWeek: firstDayOfWeek, ...props }) {
    let ref = (0, $kBAs3$react.useRef)(null);
    let { cellProps: cellProps, buttonProps: buttonProps, isPressed: isPressed, isSelected: isSelected, isDisabled: isDisabled, isFocused: isFocused, isInvalid: isInvalid, formattedDate: formattedDate } = (0, $kBAs3$reactariauseCalendar.useCalendarCell)({
        ...props,
        isDisabled: !(0, $kBAs3$internationalizeddate.isSameMonth)(props.date, currentMonth)
    }, state, ref);
    let isUnavailable = state.isCellUnavailable(props.date) && !isDisabled;
    let isLastSelectedBeforeDisabled = !isDisabled && !isInvalid && state.isCellUnavailable(props.date.add({
        days: 1
    }));
    let isFirstSelectedAfterDisabled = !isDisabled && !isInvalid && state.isCellUnavailable(props.date.subtract({
        days: 1
    }));
    let highlightedRange = 'highlightedRange' in state && state.highlightedRange;
    let isSelectionStart = isSelected && highlightedRange && (0, $kBAs3$internationalizeddate.isSameDay)(props.date, highlightedRange.start);
    let isSelectionEnd = isSelected && highlightedRange && (0, $kBAs3$internationalizeddate.isSameDay)(props.date, highlightedRange.end);
    let { locale: locale } = (0, $kBAs3$reactariaI18nProvider.useLocale)();
    let dayOfWeek = (0, $kBAs3$internationalizeddate.getDayOfWeek)(props.date, locale, firstDayOfWeek);
    let isRangeStart = isSelected && (isFirstSelectedAfterDisabled || dayOfWeek === 0 || props.date.day === 1);
    let isRangeEnd = isSelected && (isLastSelectedBeforeDisabled || dayOfWeek === 6 || props.date.day === currentMonth.calendar.getDaysInMonth(currentMonth));
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $kBAs3$reactariauseFocusRing.useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $kBAs3$reactariauseHover.useHover)({
        isDisabled: isDisabled || isUnavailable || state.isReadOnly
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($kBAs3$react))).createElement("td", {
        ...cellProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar-tableCell')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($kBAs3$react))).createElement("span", {
        ...(0, $kBAs3$reactariamergeProps.mergeProps)(buttonProps, hoverProps, focusProps),
        ref: ref,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar-date', {
            'is-today': (0, $kBAs3$internationalizeddate.isToday)(props.date, state.timeZone),
            'is-selected': isSelected,
            'is-focused': isFocused && isFocusVisible,
            // Style disabled (i.e. out of min/max range), but selected dates as unavailable
            // since it is more clear than trying to dim the selection.
            'is-disabled': isDisabled && !isInvalid,
            'is-unavailable': isUnavailable || isInvalid && isDisabled,
            'is-outsideMonth': !(0, $kBAs3$internationalizeddate.isSameMonth)(props.date, currentMonth),
            'is-range-start': isRangeStart,
            'is-range-end': isRangeEnd,
            'is-range-selection': isSelected && 'highlightedRange' in state,
            'is-selection-start': isSelectionStart,
            'is-selection-end': isSelectionEnd,
            'is-hovered': isHovered,
            'is-pressed': isPressed && !state.isReadOnly,
            'is-invalid': isInvalid
        })
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($kBAs3$react))).createElement("span", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar-dateText')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($kBAs3$react))).createElement("span", null, formattedDate))));
}


//# sourceMappingURL=CalendarCell.cjs.map
