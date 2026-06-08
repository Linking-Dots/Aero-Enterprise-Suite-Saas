import {CalendarBase as $96977f43ca009e15$export$bfd52a43017368fe} from "./CalendarBase.js";
import {createDOMRef as $c234463e9ef56637$export$a5795cc979dfae80} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useRangeCalendar as $cxfR8$useRangeCalendar} from "react-aria/useRangeCalendar";
import {createCalendar as $cxfR8$createCalendar} from "@internationalized/date";
import $cxfR8$react, {useMemo as $cxfR8$useMemo, useRef as $cxfR8$useRef, useImperativeHandle as $cxfR8$useImperativeHandle} from "react";
import {useLocale as $cxfR8$useLocale} from "react-aria/I18nProvider";
import {useRangeCalendarState as $cxfR8$useRangeCalendarState} from "react-stately/useRangeCalendarState";

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







const $ebb070240f5bf202$export$a4f5c8b89d277a8d = /*#__PURE__*/ (0, $cxfR8$react).forwardRef(function RangeCalendar(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { visibleMonths: visibleMonths = 1 } = props;
    visibleMonths = Math.max(visibleMonths, 1);
    let visibleDuration = (0, $cxfR8$useMemo)(()=>({
            months: visibleMonths
        }), [
        visibleMonths
    ]);
    let { locale: locale } = (0, $cxfR8$useLocale)();
    let state = (0, $cxfR8$useRangeCalendarState)({
        ...props,
        locale: locale,
        visibleDuration: visibleDuration,
        createCalendar: props.createCalendar || (0, $cxfR8$createCalendar)
    });
    let domRef = (0, $cxfR8$useRef)(null);
    (0, $cxfR8$useImperativeHandle)(ref, ()=>({
            ...(0, $c234463e9ef56637$export$a5795cc979dfae80)(domRef),
            focus () {
                state.setFocused(true);
            }
        }));
    let { calendarProps: calendarProps, prevButtonProps: prevButtonProps, nextButtonProps: nextButtonProps, errorMessageProps: errorMessageProps } = (0, $cxfR8$useRangeCalendar)(props, state, domRef);
    return /*#__PURE__*/ (0, $cxfR8$react).createElement((0, $96977f43ca009e15$export$bfd52a43017368fe), {
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


export {$ebb070240f5bf202$export$a4f5c8b89d277a8d as RangeCalendar};
//# sourceMappingURL=RangeCalendar.js.map
