import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {DatePickerSegment as $5bd9e661d85b8808$export$6388987c5223b54e} from "./DatePickerSegment.js";
import "./styles.css";
import $3Tdcy$styles_cssmjs from "./styles_css.mjs";
import {Field as $3967792f95357356$export$a455218a85c89869} from "../label/Field.js";
import {Input as $f0b9f6972621ffb5$export$f5b8910cec6cf069} from "./Input.js";
import {useFocusManagerRef as $14b5acfdaf2344b2$export$71a23a36270e4bf0, useFormattedDateWidth as $14b5acfdaf2344b2$export$31e22e3c931fc056} from "./utils.js";
import {useFormProps as $d23ca6800ac02cf1$export$a6b5be5c6b451665} from "../form/Form.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useTimeField as $3Tdcy$useTimeField} from "react-aria/useTimeField";
import $3Tdcy$react, {useRef as $3Tdcy$useRef} from "react";
import {useLocale as $3Tdcy$useLocale} from "react-aria/I18nProvider";
import {useTimeFieldState as $3Tdcy$useTimeFieldState} from "react-stately/useTimeFieldState";


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











const $404615aff7fb8653$export$5eaee2322dd727eb = /*#__PURE__*/ (0, $3Tdcy$react).forwardRef(function TimeField(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    props = (0, $d23ca6800ac02cf1$export$a6b5be5c6b451665)(props);
    let { autoFocus: autoFocus, isDisabled: isDisabled, isReadOnly: isReadOnly, isRequired: isRequired, isQuiet: isQuiet } = props;
    let domRef = (0, $14b5acfdaf2344b2$export$71a23a36270e4bf0)(ref);
    let { locale: locale } = (0, $3Tdcy$useLocale)();
    let state = (0, $3Tdcy$useTimeFieldState)({
        ...props,
        locale: locale
    });
    let fieldRef = (0, $3Tdcy$useRef)(null);
    let inputRef = (0, $3Tdcy$useRef)(null);
    let { labelProps: labelProps, fieldProps: fieldProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isInvalid: isInvalid, validationErrors: validationErrors, validationDetails: validationDetails } = (0, $3Tdcy$useTimeField)({
        ...props,
        inputRef: inputRef
    }, state, fieldRef);
    let validationState = state.validationState || (isInvalid ? 'invalid' : null);
    let approximateWidth = (0, $14b5acfdaf2344b2$export$31e22e3c931fc056)(state) + 'ch';
    return /*#__PURE__*/ (0, $3Tdcy$react).createElement((0, $3967792f95357356$export$a455218a85c89869), {
        ...props,
        ref: domRef,
        elementType: "span",
        labelProps: labelProps,
        descriptionProps: descriptionProps,
        errorMessageProps: errorMessageProps,
        validationState: validationState !== null && validationState !== void 0 ? validationState : undefined,
        isInvalid: isInvalid,
        validationErrors: validationErrors,
        validationDetails: validationDetails,
        wrapperClassName: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3Tdcy$styles_cssmjs))), 'react-spectrum-TimeField-fieldWrapper')
    }, /*#__PURE__*/ (0, $3Tdcy$react).createElement((0, $f0b9f6972621ffb5$export$f5b8910cec6cf069), {
        ref: fieldRef,
        fieldProps: fieldProps,
        isDisabled: isDisabled,
        isQuiet: isQuiet,
        autoFocus: autoFocus,
        validationState: validationState,
        minWidth: approximateWidth,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3Tdcy$styles_cssmjs))), 'react-spectrum-TimeField')
    }, state.segments.map((segment, i)=>/*#__PURE__*/ (0, $3Tdcy$react).createElement((0, $5bd9e661d85b8808$export$6388987c5223b54e), {
            key: i,
            segment: segment,
            state: state,
            isDisabled: isDisabled,
            isReadOnly: isReadOnly,
            isRequired: isRequired
        })), /*#__PURE__*/ (0, $3Tdcy$react).createElement("input", {
        ...inputProps,
        ref: inputRef
    })));
});


export {$404615aff7fb8653$export$5eaee2322dd727eb as TimeField};
//# sourceMappingURL=TimeField.js.map
