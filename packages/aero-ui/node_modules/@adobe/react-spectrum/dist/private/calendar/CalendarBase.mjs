import {ActionButton as $b41412308e87d8d9$export$cfc7921d29ef7b80} from "../button/ActionButton.mjs";
import {CalendarMonth as $c39ec99753b5374e$export$26e2752316b9a375} from "./CalendarMonth.mjs";
import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {HelpText as $ef3f0b17611eb293$export$a67c0bc59081311a} from "../label/HelpText.mjs";
import $3xrML$intlStringsmjs from "./intlStrings.mjs";
import "../calendar_vars.css";
import $3xrML$calendar_vars_cssmjs from "../calendar_vars_css.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import $3xrML$spectrumiconsuiChevronLeftLarge from "@spectrum-icons/ui/ChevronLeftLarge";
import $3xrML$spectrumiconsuiChevronRightLarge from "@spectrum-icons/ui/ChevronRightLarge";
import $3xrML$react from "react";
import {useDateFormatter as $3xrML$useDateFormatter} from "react-aria/useDateFormatter";
import {useLocale as $3xrML$useLocale} from "react-aria/I18nProvider";
import {useLocalizedStringFormatter as $3xrML$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";
import {VisuallyHidden as $3xrML$VisuallyHidden} from "react-aria/VisuallyHidden";


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













function $0b16e5cbd9dd3a72$export$bfd52a43017368fe(props) {
    let { state: state, calendarProps: calendarProps, nextButtonProps: nextButtonProps, prevButtonProps: prevButtonProps, errorMessageProps: errorMessageProps, calendarRef: ref, visibleMonths: visibleMonths = 1, firstDayOfWeek: firstDayOfWeek } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let stringFormatter = (0, $3xrML$useLocalizedStringFormatter)((0, ($parcel$interopDefault($3xrML$intlStringsmjs))), '@react-spectrum/calendar');
    let { direction: direction } = (0, $3xrML$useLocale)();
    let currentMonth = state.visibleRange.start;
    let monthDateFormatter = (0, $3xrML$useDateFormatter)({
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
        titles.push(/*#__PURE__*/ (0, $3xrML$react).createElement("div", {
            key: i,
            className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3xrML$calendar_vars_cssmjs))), 'spectrum-Calendar-monthHeader')
        }, i === 0 && /*#__PURE__*/ (0, $3xrML$react).createElement((0, $b41412308e87d8d9$export$cfc7921d29ef7b80), {
            ...prevButtonProps,
            UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3xrML$calendar_vars_cssmjs))), 'spectrum-Calendar-prevMonth'),
            isQuiet: true
        }, direction === 'rtl' ? /*#__PURE__*/ (0, $3xrML$react).createElement((0, $3xrML$spectrumiconsuiChevronRightLarge), null) : /*#__PURE__*/ (0, $3xrML$react).createElement((0, $3xrML$spectrumiconsuiChevronLeftLarge), null)), /*#__PURE__*/ (0, $3xrML$react).createElement("h2", {
            // We have a visually hidden heading describing the entire visible range,
            // and the calendar itself describes the individual month
            // so we don't need to repeat that here for screen reader users.
            "aria-hidden": true,
            className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3xrML$calendar_vars_cssmjs))), 'spectrum-Calendar-title')
        }, $0b16e5cbd9dd3a72$var$getCurrentMonthName(d, state.timeZone, monthDateFormatter)), i === visibleMonths - 1 && /*#__PURE__*/ (0, $3xrML$react).createElement((0, $b41412308e87d8d9$export$cfc7921d29ef7b80), {
            ...nextButtonProps,
            UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3xrML$calendar_vars_cssmjs))), 'spectrum-Calendar-nextMonth'),
            isQuiet: true
        }, direction === 'rtl' ? /*#__PURE__*/ (0, $3xrML$react).createElement((0, $3xrML$spectrumiconsuiChevronLeftLarge), null) : /*#__PURE__*/ (0, $3xrML$react).createElement((0, $3xrML$spectrumiconsuiChevronRightLarge), null))));
        calendars.push(/*#__PURE__*/ (0, $3xrML$react).createElement((0, $c39ec99753b5374e$export$26e2752316b9a375), {
            ...props,
            key: i,
            state: state,
            startDate: d,
            firstDayOfWeek: firstDayOfWeek
        }));
    }
    return /*#__PURE__*/ (0, $3xrML$react).createElement("div", {
        ...styleProps,
        ...calendarProps,
        ref: ref,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3xrML$calendar_vars_cssmjs))), 'spectrum-Calendar', styleProps.className)
    }, /*#__PURE__*/ (0, $3xrML$react).createElement((0, $3xrML$VisuallyHidden), null, /*#__PURE__*/ (0, $3xrML$react).createElement("h2", null, calendarProps['aria-label'])), /*#__PURE__*/ (0, $3xrML$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3xrML$calendar_vars_cssmjs))), 'spectrum-Calendar-header')
    }, titles), /*#__PURE__*/ (0, $3xrML$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3xrML$calendar_vars_cssmjs))), 'spectrum-Calendar-months')
    }, calendars), /*#__PURE__*/ (0, $3xrML$react).createElement((0, $3xrML$VisuallyHidden), null, /*#__PURE__*/ (0, $3xrML$react).createElement("button", {
        "aria-label": nextButtonProps['aria-label'],
        disabled: nextButtonProps.isDisabled,
        onClick: ()=>state.focusNextPage(),
        tabIndex: -1
    })), state.isValueInvalid && /*#__PURE__*/ (0, $3xrML$react).createElement((0, $ef3f0b17611eb293$export$a67c0bc59081311a), {
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
function $0b16e5cbd9dd3a72$var$getCurrentMonthName(date, timezone, monthDateFormatter) {
    if (date.calendar.getFormattableMonth) date = date.calendar.getFormattableMonth(date);
    return monthDateFormatter.format(date.toDate(timezone));
}


export {$0b16e5cbd9dd3a72$export$bfd52a43017368fe as CalendarBase};
//# sourceMappingURL=CalendarBase.mjs.map
