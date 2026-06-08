import {CalendarBase as $0b16e5cbd9dd3a72$export$bfd52a43017368fe} from "./CalendarBase.mjs";
import {createDOMRef as $3c2c983d5210446c$export$a5795cc979dfae80} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useCalendar as $iuZ6C$useCalendar} from "react-aria/useCalendar";
import {createCalendar as $iuZ6C$createCalendar} from "@internationalized/date";
import $iuZ6C$react, {useMemo as $iuZ6C$useMemo, useRef as $iuZ6C$useRef, useImperativeHandle as $iuZ6C$useImperativeHandle} from "react";
import {useCalendarState as $iuZ6C$useCalendarState} from "react-stately/useCalendarState";
import {useLocale as $iuZ6C$useLocale} from "react-aria/I18nProvider";

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







const $2c7445ecfb23a840$export$e1aef45b828286de = /*#__PURE__*/ (0, $iuZ6C$react).forwardRef(function Calendar(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { visibleMonths: visibleMonths = 1 } = props;
    visibleMonths = Math.max(visibleMonths, 1);
    let visibleDuration = (0, $iuZ6C$useMemo)(()=>({
            months: visibleMonths
        }), [
        visibleMonths
    ]);
    let { locale: locale } = (0, $iuZ6C$useLocale)();
    let state = (0, $iuZ6C$useCalendarState)({
        ...props,
        locale: locale,
        visibleDuration: visibleDuration,
        createCalendar: props.createCalendar || (0, $iuZ6C$createCalendar)
    });
    let domRef = (0, $iuZ6C$useRef)(null);
    (0, $iuZ6C$useImperativeHandle)(ref, ()=>({
            ...(0, $3c2c983d5210446c$export$a5795cc979dfae80)(domRef),
            focus () {
                state.setFocused(true);
            }
        }));
    let { calendarProps: calendarProps, prevButtonProps: prevButtonProps, nextButtonProps: nextButtonProps, errorMessageProps: errorMessageProps } = (0, $iuZ6C$useCalendar)(props, state);
    return /*#__PURE__*/ (0, $iuZ6C$react).createElement((0, $0b16e5cbd9dd3a72$export$bfd52a43017368fe), {
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


export {$2c7445ecfb23a840$export$e1aef45b828286de as Calendar};
//# sourceMappingURL=Calendar.mjs.map
