import {ActionButton as $c265dbb41bfd0210$export$cfc7921d29ef7b80} from "../button/ActionButton.js";
import {CalendarMonth as $fa86e5de1ac3908b$export$26e2752316b9a375} from "./CalendarMonth.js";
import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {HelpText as $a24709aa19b9016d$export$a67c0bc59081311a} from "../label/HelpText.js";
import $brK01$intlStringsjs from "./intlStrings.js";
import "../calendar_vars.css";
import $brK01$calendar_vars_cssmjs from "../calendar_vars_css.mjs";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import $brK01$spectrumiconsuiChevronLeftLarge from "@spectrum-icons/ui/ChevronLeftLarge";
import $brK01$spectrumiconsuiChevronRightLarge from "@spectrum-icons/ui/ChevronRightLarge";
import $brK01$react from "react";
import {useDateFormatter as $brK01$useDateFormatter} from "react-aria/useDateFormatter";
import {useLocale as $brK01$useLocale} from "react-aria/I18nProvider";
import {useLocalizedStringFormatter as $brK01$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";
import {VisuallyHidden as $brK01$VisuallyHidden} from "react-aria/VisuallyHidden";


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













function $96977f43ca009e15$export$bfd52a43017368fe(props) {
    let { state: state, calendarProps: calendarProps, nextButtonProps: nextButtonProps, prevButtonProps: prevButtonProps, errorMessageProps: errorMessageProps, calendarRef: ref, visibleMonths: visibleMonths = 1, firstDayOfWeek: firstDayOfWeek } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let stringFormatter = (0, $brK01$useLocalizedStringFormatter)((0, ($parcel$interopDefault($brK01$intlStringsjs))), '@react-spectrum/calendar');
    let { direction: direction } = (0, $brK01$useLocale)();
    let currentMonth = state.visibleRange.start;
    let monthDateFormatter = (0, $brK01$useDateFormatter)({
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
        titles.push(/*#__PURE__*/ (0, $brK01$react).createElement("div", {
            key: i,
            className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($brK01$calendar_vars_cssmjs))), 'spectrum-Calendar-monthHeader')
        }, i === 0 && /*#__PURE__*/ (0, $brK01$react).createElement((0, $c265dbb41bfd0210$export$cfc7921d29ef7b80), {
            ...prevButtonProps,
            UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($brK01$calendar_vars_cssmjs))), 'spectrum-Calendar-prevMonth'),
            isQuiet: true
        }, direction === 'rtl' ? /*#__PURE__*/ (0, $brK01$react).createElement((0, $brK01$spectrumiconsuiChevronRightLarge), null) : /*#__PURE__*/ (0, $brK01$react).createElement((0, $brK01$spectrumiconsuiChevronLeftLarge), null)), /*#__PURE__*/ (0, $brK01$react).createElement("h2", {
            // We have a visually hidden heading describing the entire visible range,
            // and the calendar itself describes the individual month
            // so we don't need to repeat that here for screen reader users.
            "aria-hidden": true,
            className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($brK01$calendar_vars_cssmjs))), 'spectrum-Calendar-title')
        }, $96977f43ca009e15$var$getCurrentMonthName(d, state.timeZone, monthDateFormatter)), i === visibleMonths - 1 && /*#__PURE__*/ (0, $brK01$react).createElement((0, $c265dbb41bfd0210$export$cfc7921d29ef7b80), {
            ...nextButtonProps,
            UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($brK01$calendar_vars_cssmjs))), 'spectrum-Calendar-nextMonth'),
            isQuiet: true
        }, direction === 'rtl' ? /*#__PURE__*/ (0, $brK01$react).createElement((0, $brK01$spectrumiconsuiChevronLeftLarge), null) : /*#__PURE__*/ (0, $brK01$react).createElement((0, $brK01$spectrumiconsuiChevronRightLarge), null))));
        calendars.push(/*#__PURE__*/ (0, $brK01$react).createElement((0, $fa86e5de1ac3908b$export$26e2752316b9a375), {
            ...props,
            key: i,
            state: state,
            startDate: d,
            firstDayOfWeek: firstDayOfWeek
        }));
    }
    return /*#__PURE__*/ (0, $brK01$react).createElement("div", {
        ...styleProps,
        ...calendarProps,
        ref: ref,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($brK01$calendar_vars_cssmjs))), 'spectrum-Calendar', styleProps.className)
    }, /*#__PURE__*/ (0, $brK01$react).createElement((0, $brK01$VisuallyHidden), null, /*#__PURE__*/ (0, $brK01$react).createElement("h2", null, calendarProps['aria-label'])), /*#__PURE__*/ (0, $brK01$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($brK01$calendar_vars_cssmjs))), 'spectrum-Calendar-header')
    }, titles), /*#__PURE__*/ (0, $brK01$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($brK01$calendar_vars_cssmjs))), 'spectrum-Calendar-months')
    }, calendars), /*#__PURE__*/ (0, $brK01$react).createElement((0, $brK01$VisuallyHidden), null, /*#__PURE__*/ (0, $brK01$react).createElement("button", {
        "aria-label": nextButtonProps['aria-label'],
        disabled: nextButtonProps.isDisabled,
        onClick: ()=>state.focusNextPage(),
        tabIndex: -1
    })), state.isValueInvalid && /*#__PURE__*/ (0, $brK01$react).createElement((0, $a24709aa19b9016d$export$a67c0bc59081311a), {
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
function $96977f43ca009e15$var$getCurrentMonthName(date, timezone, monthDateFormatter) {
    if (date.calendar.getFormattableMonth) date = date.calendar.getFormattableMonth(date);
    return monthDateFormatter.format(date.toDate(timezone));
}


export {$96977f43ca009e15$export$bfd52a43017368fe as CalendarBase};
//# sourceMappingURL=CalendarBase.js.map
