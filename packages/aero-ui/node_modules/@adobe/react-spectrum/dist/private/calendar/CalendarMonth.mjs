import {CalendarCell as $e70370a79212983d$export$5d847498420df57b} from "./CalendarCell.mjs";
import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../calendar_vars.css";
import $cR0fI$calendar_vars_cssmjs from "../calendar_vars_css.mjs";
import {endOfMonth as $cR0fI$endOfMonth} from "@internationalized/date";
import $cR0fI$react from "react";
import {useCalendarGrid as $cR0fI$useCalendarGrid} from "react-aria/useCalendar";


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





function $c39ec99753b5374e$export$26e2752316b9a375(props) {
    let { state: state, startDate: startDate, firstDayOfWeek: firstDayOfWeek } = props;
    let { gridProps: gridProps, headerProps: headerProps, weekDays: weekDays, weeksInMonth: weeksInMonth } = (0, $cR0fI$useCalendarGrid)({
        ...props,
        endDate: (0, $cR0fI$endOfMonth)(startDate)
    }, state);
    return /*#__PURE__*/ (0, $cR0fI$react).createElement("table", {
        ...gridProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cR0fI$calendar_vars_cssmjs))), 'spectrum-Calendar-body', 'spectrum-Calendar-table')
    }, /*#__PURE__*/ (0, $cR0fI$react).createElement("thead", headerProps, /*#__PURE__*/ (0, $cR0fI$react).createElement("tr", null, weekDays.map((day, index)=>/*#__PURE__*/ (0, $cR0fI$react).createElement("th", {
            key: index,
            className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cR0fI$calendar_vars_cssmjs))), 'spectrum-Calendar-tableCell')
        }, /*#__PURE__*/ (0, $cR0fI$react).createElement("span", {
            className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cR0fI$calendar_vars_cssmjs))), 'spectrum-Calendar-dayOfWeek')
        }, day))))), /*#__PURE__*/ (0, $cR0fI$react).createElement("tbody", null, [
        ...new Array(weeksInMonth).keys()
    ].map((weekIndex)=>/*#__PURE__*/ (0, $cR0fI$react).createElement("tr", {
            key: weekIndex
        }, state.getDatesInWeek(weekIndex, startDate).map((date, i)=>date ? /*#__PURE__*/ (0, $cR0fI$react).createElement((0, $e70370a79212983d$export$5d847498420df57b), {
                key: i,
                state: state,
                date: date,
                currentMonth: startDate,
                firstDayOfWeek: firstDayOfWeek
            }) : /*#__PURE__*/ (0, $cR0fI$react).createElement("td", {
                key: i
            }))))));
}


export {$c39ec99753b5374e$export$26e2752316b9a375 as CalendarMonth};
//# sourceMappingURL=CalendarMonth.mjs.map
