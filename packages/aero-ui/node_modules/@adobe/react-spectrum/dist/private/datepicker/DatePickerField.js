import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {DatePickerSegment as $5bd9e661d85b8808$export$6388987c5223b54e} from "./DatePickerSegment.js";
import "./styles.css";
import $6IMZq$styles_cssmjs from "./styles_css.mjs";
import {createCalendar as $6IMZq$createCalendar} from "@internationalized/date";
import {useDateField as $6IMZq$useDateField} from "react-aria/useDateField";
import $6IMZq$react, {useRef as $6IMZq$useRef} from "react";
import {useDateFieldState as $6IMZq$useDateFieldState} from "react-stately/useDateFieldState";
import {useLocale as $6IMZq$useLocale} from "react-aria/I18nProvider";


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







function $219f26d0f9cef050$export$34dc4cfa15ead1(props) {
    let { isDisabled: isDisabled, isReadOnly: isReadOnly, isRequired: isRequired, inputClassName: inputClassName } = props;
    let ref = (0, $6IMZq$useRef)(null);
    let { locale: locale } = (0, $6IMZq$useLocale)();
    let state = (0, $6IMZq$useDateFieldState)({
        ...props,
        locale: locale,
        createCalendar: $6IMZq$createCalendar
    });
    let inputRef = (0, $6IMZq$useRef)(null);
    let { fieldProps: fieldProps, inputProps: inputProps } = (0, $6IMZq$useDateField)({
        ...props,
        inputRef: inputRef
    }, state, ref);
    return /*#__PURE__*/ (0, $6IMZq$react).createElement("span", {
        ...fieldProps,
        "data-testid": props['data-testid'],
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($6IMZq$styles_cssmjs))), 'react-spectrum-Datepicker-segments', inputClassName),
        ref: ref
    }, state.segments.map((segment, i)=>/*#__PURE__*/ (0, $6IMZq$react).createElement((0, $5bd9e661d85b8808$export$6388987c5223b54e), {
            key: i,
            segment: segment,
            state: state,
            isDisabled: isDisabled,
            isReadOnly: isReadOnly,
            isRequired: isRequired
        })), /*#__PURE__*/ (0, $6IMZq$react).createElement("input", {
        ...inputProps,
        ref: inputRef
    }));
}


export {$219f26d0f9cef050$export$34dc4cfa15ead1 as DatePickerField};
//# sourceMappingURL=DatePickerField.js.map
