import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {DatePickerSegment as $8fe48145f400a5cb$export$6388987c5223b54e} from "./DatePickerSegment.mjs";
import "./styles.css";
import $ia7jf$styles_cssmjs from "./styles_css.mjs";
import {createCalendar as $ia7jf$createCalendar} from "@internationalized/date";
import {useDateField as $ia7jf$useDateField} from "react-aria/useDateField";
import $ia7jf$react, {useRef as $ia7jf$useRef} from "react";
import {useDateFieldState as $ia7jf$useDateFieldState} from "react-stately/useDateFieldState";
import {useLocale as $ia7jf$useLocale} from "react-aria/I18nProvider";


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







function $ac97b87622c58dce$export$34dc4cfa15ead1(props) {
    let { isDisabled: isDisabled, isReadOnly: isReadOnly, isRequired: isRequired, inputClassName: inputClassName } = props;
    let ref = (0, $ia7jf$useRef)(null);
    let { locale: locale } = (0, $ia7jf$useLocale)();
    let state = (0, $ia7jf$useDateFieldState)({
        ...props,
        locale: locale,
        createCalendar: $ia7jf$createCalendar
    });
    let inputRef = (0, $ia7jf$useRef)(null);
    let { fieldProps: fieldProps, inputProps: inputProps } = (0, $ia7jf$useDateField)({
        ...props,
        inputRef: inputRef
    }, state, ref);
    return /*#__PURE__*/ (0, $ia7jf$react).createElement("span", {
        ...fieldProps,
        "data-testid": props['data-testid'],
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ia7jf$styles_cssmjs))), 'react-spectrum-Datepicker-segments', inputClassName),
        ref: ref
    }, state.segments.map((segment, i)=>/*#__PURE__*/ (0, $ia7jf$react).createElement((0, $8fe48145f400a5cb$export$6388987c5223b54e), {
            key: i,
            segment: segment,
            state: state,
            isDisabled: isDisabled,
            isReadOnly: isReadOnly,
            isRequired: isRequired
        })), /*#__PURE__*/ (0, $ia7jf$react).createElement("input", {
        ...inputProps,
        ref: inputRef
    }));
}


export {$ac97b87622c58dce$export$34dc4cfa15ead1 as DatePickerField};
//# sourceMappingURL=DatePickerField.mjs.map
