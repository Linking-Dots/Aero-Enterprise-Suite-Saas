import {CalendarBase as $96977f43ca009e15$export$bfd52a43017368fe} from "./CalendarBase.js";
import {createDOMRef as $c234463e9ef56637$export$a5795cc979dfae80} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useCalendar as $Ndo6V$useCalendar} from "react-aria/useCalendar";
import {createCalendar as $Ndo6V$createCalendar} from "@internationalized/date";
import $Ndo6V$react, {useMemo as $Ndo6V$useMemo, useRef as $Ndo6V$useRef, useImperativeHandle as $Ndo6V$useImperativeHandle} from "react";
import {useCalendarState as $Ndo6V$useCalendarState} from "react-stately/useCalendarState";
import {useLocale as $Ndo6V$useLocale} from "react-aria/I18nProvider";

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







const $056e9579a2d4c8dd$export$e1aef45b828286de = /*#__PURE__*/ (0, $Ndo6V$react).forwardRef(function Calendar(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { visibleMonths: visibleMonths = 1 } = props;
    visibleMonths = Math.max(visibleMonths, 1);
    let visibleDuration = (0, $Ndo6V$useMemo)(()=>({
            months: visibleMonths
        }), [
        visibleMonths
    ]);
    let { locale: locale } = (0, $Ndo6V$useLocale)();
    let state = (0, $Ndo6V$useCalendarState)({
        ...props,
        locale: locale,
        visibleDuration: visibleDuration,
        createCalendar: props.createCalendar || (0, $Ndo6V$createCalendar)
    });
    let domRef = (0, $Ndo6V$useRef)(null);
    (0, $Ndo6V$useImperativeHandle)(ref, ()=>({
            ...(0, $c234463e9ef56637$export$a5795cc979dfae80)(domRef),
            focus () {
                state.setFocused(true);
            }
        }));
    let { calendarProps: calendarProps, prevButtonProps: prevButtonProps, nextButtonProps: nextButtonProps, errorMessageProps: errorMessageProps } = (0, $Ndo6V$useCalendar)(props, state);
    return /*#__PURE__*/ (0, $Ndo6V$react).createElement((0, $96977f43ca009e15$export$bfd52a43017368fe), {
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


export {$056e9579a2d4c8dd$export$e1aef45b828286de as Calendar};
//# sourceMappingURL=Calendar.js.map
