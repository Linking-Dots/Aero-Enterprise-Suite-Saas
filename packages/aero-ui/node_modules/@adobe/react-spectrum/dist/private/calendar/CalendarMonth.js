import {CalendarCell as $46d831f1160df915$export$5d847498420df57b} from "./CalendarCell.js";
import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../calendar_vars.css";
import $c5e2U$calendar_vars_cssmjs from "../calendar_vars_css.mjs";
import {endOfMonth as $c5e2U$endOfMonth} from "@internationalized/date";
import $c5e2U$react from "react";
import {useCalendarGrid as $c5e2U$useCalendarGrid} from "react-aria/useCalendar";


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





function $fa86e5de1ac3908b$export$26e2752316b9a375(props) {
    let { state: state, startDate: startDate, firstDayOfWeek: firstDayOfWeek } = props;
    let { gridProps: gridProps, headerProps: headerProps, weekDays: weekDays, weeksInMonth: weeksInMonth } = (0, $c5e2U$useCalendarGrid)({
        ...props,
        endDate: (0, $c5e2U$endOfMonth)(startDate)
    }, state);
    return /*#__PURE__*/ (0, $c5e2U$react).createElement("table", {
        ...gridProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($c5e2U$calendar_vars_cssmjs))), 'spectrum-Calendar-body', 'spectrum-Calendar-table')
    }, /*#__PURE__*/ (0, $c5e2U$react).createElement("thead", headerProps, /*#__PURE__*/ (0, $c5e2U$react).createElement("tr", null, weekDays.map((day, index)=>/*#__PURE__*/ (0, $c5e2U$react).createElement("th", {
            key: index,
            className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($c5e2U$calendar_vars_cssmjs))), 'spectrum-Calendar-tableCell')
        }, /*#__PURE__*/ (0, $c5e2U$react).createElement("span", {
            className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($c5e2U$calendar_vars_cssmjs))), 'spectrum-Calendar-dayOfWeek')
        }, day))))), /*#__PURE__*/ (0, $c5e2U$react).createElement("tbody", null, [
        ...new Array(weeksInMonth).keys()
    ].map((weekIndex)=>/*#__PURE__*/ (0, $c5e2U$react).createElement("tr", {
            key: weekIndex
        }, state.getDatesInWeek(weekIndex, startDate).map((date, i)=>date ? /*#__PURE__*/ (0, $c5e2U$react).createElement((0, $46d831f1160df915$export$5d847498420df57b), {
                key: i,
                state: state,
                date: date,
                currentMonth: startDate,
                firstDayOfWeek: firstDayOfWeek
            }) : /*#__PURE__*/ (0, $c5e2U$react).createElement("td", {
                key: i
            }))))));
}


export {$fa86e5de1ac3908b$export$26e2752316b9a375 as CalendarMonth};
//# sourceMappingURL=CalendarMonth.js.map
