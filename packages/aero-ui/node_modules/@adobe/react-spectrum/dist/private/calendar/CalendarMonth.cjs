var $9e60f7064368183d$exports = require("./CalendarCell.cjs");
var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../calendar_vars.css");
var $7671a6feef2ac7d1$exports = require("../calendar_vars_css.cjs");
var $65pu6$internationalizeddate = require("@internationalized/date");
var $65pu6$react = require("react");
var $65pu6$reactariauseCalendar = require("react-aria/useCalendar");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "CalendarMonth", function () { return $4d62270bd2fa40b2$export$26e2752316b9a375; });
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





function $4d62270bd2fa40b2$export$26e2752316b9a375(props) {
    let { state: state, startDate: startDate, firstDayOfWeek: firstDayOfWeek } = props;
    let { gridProps: gridProps, headerProps: headerProps, weekDays: weekDays, weeksInMonth: weeksInMonth } = (0, $65pu6$reactariauseCalendar.useCalendarGrid)({
        ...props,
        endDate: (0, $65pu6$internationalizeddate.endOfMonth)(startDate)
    }, state);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($65pu6$react))).createElement("table", {
        ...gridProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar-body', 'spectrum-Calendar-table')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($65pu6$react))).createElement("thead", headerProps, /*#__PURE__*/ (0, ($parcel$interopDefault($65pu6$react))).createElement("tr", null, weekDays.map((day, index)=>/*#__PURE__*/ (0, ($parcel$interopDefault($65pu6$react))).createElement("th", {
            key: index,
            className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar-tableCell')
        }, /*#__PURE__*/ (0, ($parcel$interopDefault($65pu6$react))).createElement("span", {
            className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7671a6feef2ac7d1$exports))), 'spectrum-Calendar-dayOfWeek')
        }, day))))), /*#__PURE__*/ (0, ($parcel$interopDefault($65pu6$react))).createElement("tbody", null, [
        ...new Array(weeksInMonth).keys()
    ].map((weekIndex)=>/*#__PURE__*/ (0, ($parcel$interopDefault($65pu6$react))).createElement("tr", {
            key: weekIndex
        }, state.getDatesInWeek(weekIndex, startDate).map((date, i)=>date ? /*#__PURE__*/ (0, ($parcel$interopDefault($65pu6$react))).createElement((0, $9e60f7064368183d$exports.CalendarCell), {
                key: i,
                state: state,
                date: date,
                currentMonth: startDate,
                firstDayOfWeek: firstDayOfWeek
            }) : /*#__PURE__*/ (0, ($parcel$interopDefault($65pu6$react))).createElement("td", {
                key: i
            }))))));
}


//# sourceMappingURL=CalendarMonth.cjs.map
