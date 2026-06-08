var $183c5173677598aa$exports = require("../button/ActionButton.cjs");
var $4d62270bd2fa40b2$exports = require("./CalendarMonth.cjs");
var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $2b77b98944e1735c$exports = require("../label/HelpText.cjs");
var $57dda9805bef32c3$exports = require("./intlStrings.cjs");
require("../calendar_vars.css");
var $7671a6feef2ac7d1$exports = require("../calendar_vars_css.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $iDqXn$spectrumiconsuiChevronLeftLarge = require("@spectrum-icons/ui/ChevronLeftLarge");
var $iDqXn$spectrumiconsuiChevronRightLarge = require("@spectrum-icons/ui/ChevronRightLarge");
var $iDqXn$react = require("react");
var $iDqXn$reactariauseDateFormatter = require("react-aria/useDateFormatter");
var $iDqXn$reactariaI18nProvider = require("react-aria/I18nProvider");
var $iDqXn$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");
var $iDqXn$reactariaVisuallyHidden = require("react-aria/VisuallyHidden");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "CalendarBase", function () { return $0dd15fcccf123c51$export$bfd52a43017368fe; });
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













function $0dd15fcccf123c51$export$bfd52a43017368fe(props) {
    let { state: state, calendarProps: calendarProps, nextButtonProps: nextButtonProps, prevButtonProps: prevButtonProps, errorMessageProps: errorMessageProps, calendarRef: ref, visibleMonths: visibleMonths = 1, firstDayOfWeek: firstDayOfWeek } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let stringFormatter = (0, $iDqXn$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($57dda9805bef32c3$exports))), '@react-spectrum/calendar');
    let { direction: direction } = (0, $iDqXn$reactariaI18nProvider.useLocale)();
    let currentMonth = state.visibleRange.start;
    let monthDateFormatter = (0, $iDqXn$reactariauseDateFormatter.useDateFormatter)({
        month: 'long',
        year: 'numeric',
        era: currentMonth.calendar.identifier === 'gregory' && currentMonth.era === 'BC' ? 'short' : undefined,
        calendar: currentMonth.calendar.identifier,
        timeZone: state.timeZone
    });
    let titles = [];
    let calendars = [];
    for(let i = 0; i < visibleMonths; i++){
        let d = currentMonth.add({
            months: i
        });
        titles.push(/*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement("div", {
            key: i,
            className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar-monthHeader')
        }, i === 0 && /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement((0, $183c5173677598aa$exports.ActionButton), {
            ...prevButtonProps,
            UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar-prevMonth'),
            isQuiet: true
        }, direction === 'rtl' ? /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement((0, ($parcel$interopDefault($iDqXn$spectrumiconsuiChevronRightLarge))), null) : /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement((0, ($parcel$interopDefault($iDqXn$spectrumiconsuiChevronLeftLarge))), null)), /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement("h2", {
            // We have a visually hidden heading describing the entire visible range,
            // and the calendar itself describes the individual month
            // so we don't need to repeat that here for screen reader users.
            "aria-hidden": true,
            className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar-title')
        }, $0dd15fcccf123c51$var$getCurrentMonthName(d, state.timeZone, monthDateFormatter)), i === visibleMonths - 1 && /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement((0, $183c5173677598aa$exports.ActionButton), {
            ...nextButtonProps,
            UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar-nextMonth'),
            isQuiet: true
        }, direction === 'rtl' ? /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement((0, ($parcel$interopDefault($iDqXn$spectrumiconsuiChevronLeftLarge))), null) : /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement((0, ($parcel$interopDefault($iDqXn$spectrumiconsuiChevronRightLarge))), null))));
        calendars.push(/*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement((0, $4d62270bd2fa40b2$exports.CalendarMonth), {
            ...props,
            key: i,
            state: state,
            startDate: d,
            firstDayOfWeek: firstDayOfWeek
        }));
    }
    return /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement("div", {
        ...styleProps,
        ...calendarProps,
        ref: ref,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar', styleProps.className)
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement((0, $iDqXn$reactariaVisuallyHidden.VisuallyHidden), null, /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement("h2", null, calendarProps['aria-label'])), /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar-header')
    }, titles), /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar-months')
    }, calendars), /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement((0, $iDqXn$reactariaVisuallyHidden.VisuallyHidden), null, /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement("button", {
        "aria-label": nextButtonProps['aria-label'],
        disabled: nextButtonProps.isDisabled,
        onClick: ()=>state.focusNextPage(),
        tabIndex: -1
    })), state.isValueInvalid && /*#__PURE__*/ (0, ($parcel$interopDefault($iDqXn$react))).createElement((0, $2b77b98944e1735c$exports.HelpText), {
        showErrorIcon: true,
        errorMessage: props.errorMessage || stringFormatter.format('invalidSelection', {
            selectedCount: 'highlightedRange' in state ? 2 : 1
        }),
        errorMessageProps: errorMessageProps,
        isInvalid: true,
        // Intentionally a global class name so it can be targeted in DatePicker CSS...
        UNSAFE_className: "spectrum-Calendar-helpText"
    }));
}
function $0dd15fcccf123c51$var$getCurrentMonthName(date, timezone, monthDateFormatter) {
    if (date.calendar.getFormattableMonth) date = date.calendar.getFormattableMonth(date);
    return monthDateFormatter.format(date.toDate(timezone));
}


//# sourceMappingURL=CalendarBase.cjs.map
