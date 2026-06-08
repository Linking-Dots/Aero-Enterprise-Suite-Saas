import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {DatePickerSegment as $8fe48145f400a5cb$export$6388987c5223b54e} from "./DatePickerSegment.mjs";
import "./styles.css";
import $2YkS6$styles_cssmjs from "./styles_css.mjs";
import {Field as $adcd096854d27620$export$a455218a85c89869} from "../label/Field.mjs";
import {Input as $51cb122633c52627$export$f5b8910cec6cf069} from "./Input.mjs";
import {useFocusManagerRef as $d24c665d02225161$export$71a23a36270e4bf0, useFormattedDateWidth as $d24c665d02225161$export$31e22e3c931fc056} from "./utils.mjs";
import {useFormProps as $c29c48d4ef19ffc4$export$a6b5be5c6b451665} from "../form/Form.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useTimeField as $2YkS6$useTimeField} from "react-aria/useTimeField";
import $2YkS6$react, {useRef as $2YkS6$useRef} from "react";
import {useLocale as $2YkS6$useLocale} from "react-aria/I18nProvider";
import {useTimeFieldState as $2YkS6$useTimeFieldState} from "react-stately/useTimeFieldState";


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











const $81f52c4b0082835e$export$5eaee2322dd727eb = /*#__PURE__*/ (0, $2YkS6$react).forwardRef(function TimeField(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    props = (0, $c29c48d4ef19ffc4$export$a6b5be5c6b451665)(props);
    let { autoFocus: autoFocus, isDisabled: isDisabled, isReadOnly: isReadOnly, isRequired: isRequired, isQuiet: isQuiet } = props;
    let domRef = (0, $d24c665d02225161$export$71a23a36270e4bf0)(ref);
    let { locale: locale } = (0, $2YkS6$useLocale)();
    let state = (0, $2YkS6$useTimeFieldState)({
        ...props,
        locale: locale
    });
    let fieldRef = (0, $2YkS6$useRef)(null);
    let inputRef = (0, $2YkS6$useRef)(null);
    let { labelProps: labelProps, fieldProps: fieldProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isInvalid: isInvalid, validationErrors: validationErrors, validationDetails: validationDetails } = (0, $2YkS6$useTimeField)({
        ...props,
        inputRef: inputRef
    }, state, fieldRef);
    let validationState = state.validationState || (isInvalid ? 'invalid' : null);
    let approximateWidth = (0, $d24c665d02225161$export$31e22e3c931fc056)(state) + 'ch';
    return /*#__PURE__*/ (0, $2YkS6$react).createElement((0, $adcd096854d27620$export$a455218a85c89869), {
        ...props,
        ref: domRef,
        elementType: "span",
        labelProps: labelProps,
        descriptionProps: descriptionProps,
        errorMessageProps: errorMessageProps,
        validationState: validationState ?? undefined,
        isInvalid: isInvalid,
        validationErrors: validationErrors,
        validationDetails: validationDetails,
        wrapperClassName: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2YkS6$styles_cssmjs))), 'react-spectrum-TimeField-fieldWrapper')
    }, /*#__PURE__*/ (0, $2YkS6$react).createElement((0, $51cb122633c52627$export$f5b8910cec6cf069), {
        ref: fieldRef,
        fieldProps: fieldProps,
        isDisabled: isDisabled,
        isQuiet: isQuiet,
        autoFocus: autoFocus,
        validationState: validationState,
        minWidth: approximateWidth,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2YkS6$styles_cssmjs))), 'react-spectrum-TimeField')
    }, state.segments.map((segment, i)=>/*#__PURE__*/ (0, $2YkS6$react).createElement((0, $8fe48145f400a5cb$export$6388987c5223b54e), {
            key: i,
            segment: segment,
            state: state,
            isDisabled: isDisabled,
            isReadOnly: isReadOnly,
            isRequired: isRequired
        })), /*#__PURE__*/ (0, $2YkS6$react).createElement("input", {
        ...inputProps,
        ref: inputRef
    })));
});


export {$81f52c4b0082835e$export$5eaee2322dd727eb as TimeField};
//# sourceMappingURL=TimeField.mjs.map
