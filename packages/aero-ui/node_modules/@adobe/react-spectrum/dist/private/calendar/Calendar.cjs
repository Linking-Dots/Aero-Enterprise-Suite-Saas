var $0dd15fcccf123c51$exports = require("./CalendarBase.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $7n3QD$reactariauseCalendar = require("react-aria/useCalendar");
var $7n3QD$internationalizeddate = require("@internationalized/date");
var $7n3QD$react = require("react");
var $7n3QD$reactstatelyuseCalendarState = require("react-stately/useCalendarState");
var $7n3QD$reactariaI18nProvider = require("react-aria/I18nProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Calendar", function () { return $943d9406146967ab$export$e1aef45b828286de; });
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







const $943d9406146967ab$export$e1aef45b828286de = /*#__PURE__*/ (0, ($parcel$interopDefault($7n3QD$react))).forwardRef(function Calendar(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { visibleMonths: visibleMonths = 1 } = props;
    visibleMonths = Math.max(visibleMonths, 1);
    let visibleDuration = (0, $7n3QD$react.useMemo)(()=>({
            months: visibleMonths
        }), [
        visibleMonths
    ]);
    let { locale: locale } = (0, $7n3QD$reactariaI18nProvider.useLocale)();
    let state = (0, $7n3QD$reactstatelyuseCalendarState.useCalendarState)({
        ...props,
        locale: locale,
        visibleDuration: visibleDuration,
        createCalendar: props.createCalendar || (0, $7n3QD$internationalizeddate.createCalendar)
    });
    let domRef = (0, $7n3QD$react.useRef)(null);
    (0, $7n3QD$react.useImperativeHandle)(ref, ()=>({
            ...(0, $65aea7b37663976b$exports.createDOMRef)(domRef),
            focus () {
                state.setFocused(true);
            }
        }));
    let { calendarProps: calendarProps, prevButtonProps: prevButtonProps, nextButtonProps: nextButtonProps, errorMessageProps: errorMessageProps } = (0, $7n3QD$reactariauseCalendar.useCalendar)(props, state);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($7n3QD$react))).createElement((0, $0dd15fcccf123c51$exports.CalendarBase), {
        ...props,
        visibleMonths: visibleMonths,
        state: state,
        calendarRef: domRef,
        calendarProps: calendarProps,
        prevButtonProps: prevButtonProps,
        nextButtonProps: nextButtonProps,
        errorMessageProps: errorMessageProps
    });
});


//# sourceMappingURL=Calendar.cjs.map
