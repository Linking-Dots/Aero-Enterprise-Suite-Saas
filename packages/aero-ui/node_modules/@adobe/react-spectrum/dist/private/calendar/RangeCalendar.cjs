var $0dd15fcccf123c51$exports = require("./CalendarBase.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $jQyYf$reactariauseRangeCalendar = require("react-aria/useRangeCalendar");
var $jQyYf$internationalizeddate = require("@internationalized/date");
var $jQyYf$react = require("react");
var $jQyYf$reactariaI18nProvider = require("react-aria/I18nProvider");
var $jQyYf$reactstatelyuseRangeCalendarState = require("react-stately/useRangeCalendarState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "RangeCalendar", function () { return $f5d9a7c2c740db4a$export$a4f5c8b89d277a8d; });
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







const $f5d9a7c2c740db4a$export$a4f5c8b89d277a8d = /*#__PURE__*/ (0, ($parcel$interopDefault($jQyYf$react))).forwardRef(function RangeCalendar(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { visibleMonths: visibleMonths = 1 } = props;
    visibleMonths = Math.max(visibleMonths, 1);
    let visibleDuration = (0, $jQyYf$react.useMemo)(()=>({
            months: visibleMonths
        }), [
        visibleMonths
    ]);
    let { locale: locale } = (0, $jQyYf$reactariaI18nProvider.useLocale)();
    let state = (0, $jQyYf$reactstatelyuseRangeCalendarState.useRangeCalendarState)({
        ...props,
        locale: locale,
        visibleDuration: visibleDuration,
        createCalendar: props.createCalendar || (0, $jQyYf$internationalizeddate.createCalendar)
    });
    let domRef = (0, $jQyYf$react.useRef)(null);
    (0, $jQyYf$react.useImperativeHandle)(ref, ()=>({
            ...(0, $65aea7b37663976b$exports.createDOMRef)(domRef),
            focus () {
                state.setFocused(true);
            }
        }));
    let { calendarProps: calendarProps, prevButtonProps: prevButtonProps, nextButtonProps: nextButtonProps, errorMessageProps: errorMessageProps } = (0, $jQyYf$reactariauseRangeCalendar.useRangeCalendar)(props, state, domRef);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($jQyYf$react))).createElement((0, $0dd15fcccf123c51$exports.CalendarBase), {
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


//# sourceMappingURL=RangeCalendar.cjs.map
