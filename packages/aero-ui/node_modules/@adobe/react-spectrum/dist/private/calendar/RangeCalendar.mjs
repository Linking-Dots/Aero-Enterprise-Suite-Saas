import {CalendarBase as $0b16e5cbd9dd3a72$export$bfd52a43017368fe} from "./CalendarBase.mjs";
import {createDOMRef as $3c2c983d5210446c$export$a5795cc979dfae80} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useRangeCalendar as $22arz$useRangeCalendar} from "react-aria/useRangeCalendar";
import {createCalendar as $22arz$createCalendar} from "@internationalized/date";
import $22arz$react, {useMemo as $22arz$useMemo, useRef as $22arz$useRef, useImperativeHandle as $22arz$useImperativeHandle} from "react";
import {useLocale as $22arz$useLocale} from "react-aria/I18nProvider";
import {useRangeCalendarState as $22arz$useRangeCalendarState} from "react-stately/useRangeCalendarState";

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







const $a03fb7d0c639da6e$export$a4f5c8b89d277a8d = /*#__PURE__*/ (0, $22arz$react).forwardRef(function RangeCalendar(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { visibleMonths: visibleMonths = 1 } = props;
    visibleMonths = Math.max(visibleMonths, 1);
    let visibleDuration = (0, $22arz$useMemo)(()=>({
            months: visibleMonths
        }), [
        visibleMonths
    ]);
    let { locale: locale } = (0, $22arz$useLocale)();
    let state = (0, $22arz$useRangeCalendarState)({
        ...props,
        locale: locale,
        visibleDuration: visibleDuration,
        createCalendar: props.createCalendar || (0, $22arz$createCalendar)
    });
    let domRef = (0, $22arz$useRef)(null);
    (0, $22arz$useImperativeHandle)(ref, ()=>({
            ...(0, $3c2c983d5210446c$export$a5795cc979dfae80)(domRef),
            focus () {
                state.setFocused(true);
            }
        }));
    let { calendarProps: calendarProps, prevButtonProps: prevButtonProps, nextButtonProps: nextButtonProps, errorMessageProps: errorMessageProps } = (0, $22arz$useRangeCalendar)(props, state, domRef);
    return /*#__PURE__*/ (0, $22arz$react).createElement((0, $0b16e5cbd9dd3a72$export$bfd52a43017368fe), {
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


export {$a03fb7d0c639da6e$export$a4f5c8b89d277a8d as RangeCalendar};
//# sourceMappingURL=RangeCalendar.mjs.map
