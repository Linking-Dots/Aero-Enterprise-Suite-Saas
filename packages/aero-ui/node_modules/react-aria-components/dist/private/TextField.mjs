import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, removeDataAttributes as $7230ffa83bc0c2cf$export$ef03459518577ad4, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlot as $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {FieldErrorContext as $1f3c3b1a70cec653$export$ff05c3ac10437e03} from "./FieldError.mjs";
import {FieldInputContext as $4b38b5b75ecc6208$export$698f465ec27e93df} from "./Autocomplete.mjs";
import {FormContext as $cdaed739b1139372$export$c24727297075ec6a} from "./Form.mjs";
import {GroupContext as $3a442827418ebe87$export$f9c6924e160136d1} from "./Group.mjs";
import {InputContext as $41fb335299a4a39e$export$37fb8590cf2c088c} from "./Input.mjs";
import {LabelContext as $43a3b93638fe5db9$export$75b6ee27786ba447} from "./Label.mjs";
import {TextAreaContext as $bd263d78e9bf3c56$export$2dc6166a7e65358c} from "./TextArea.mjs";
import {TextContext as $efe09c6d1c304b50$export$9afb8bc826b033ea} from "./Text.mjs";
import {useTextField as $hlfBT$useTextField} from "react-aria/useTextField";
import {createHideableComponent as $hlfBT$createHideableComponent} from "react-aria/private/collections/Hidden";
import {filterDOMProps as $hlfBT$filterDOMProps} from "react-aria/filterDOMProps";
import $hlfBT$react, {createContext as $hlfBT$createContext, useRef as $hlfBT$useRef, useState as $hlfBT$useState, useCallback as $hlfBT$useCallback} from "react";

/*
 * Copyright 2022 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 












const $b8dcdc58eeae0d40$export$2129e27b3ef0d483 = /*#__PURE__*/ (0, $hlfBT$createContext)(null);
const $b8dcdc58eeae0d40$export$2c73285ae9390cec = /*#__PURE__*/ (0, $hlfBT$createHideableComponent)(function TextField(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $b8dcdc58eeae0d40$export$2129e27b3ef0d483);
    let { validationBehavior: formValidationBehavior } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)((0, $cdaed739b1139372$export$c24727297075ec6a)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let inputRef = (0, $hlfBT$useRef)(null);
    [props, inputRef] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, inputRef, (0, $4b38b5b75ecc6208$export$698f465ec27e93df));
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let [inputElementType, setInputElementType] = (0, $hlfBT$useState)('input');
    let { labelProps: labelProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $hlfBT$useTextField)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        inputElementType: inputElementType,
        label: label,
        validationBehavior: validationBehavior
    }, inputRef);
    // Intercept setting the input ref so we can determine what kind of element we have.
    // useTextField uses this to determine what props to include.
    let inputOrTextAreaRef = (0, $hlfBT$useCallback)((el)=>{
        inputRef.current = el;
        if (el) setInputElementType(el instanceof HTMLTextAreaElement ? 'textarea' : 'input');
    }, [
        inputRef
    ]);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            isDisabled: props.isDisabled || false,
            isInvalid: validation.isInvalid,
            isReadOnly: props.isReadOnly || false,
            isRequired: props.isRequired || false
        },
        defaultClassName: 'react-aria-TextField'
    });
    let DOMProps = (0, $hlfBT$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $hlfBT$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": validation.isInvalid || undefined,
        "data-readonly": props.isReadOnly || undefined,
        "data-required": props.isRequired || undefined
    }, /*#__PURE__*/ (0, $hlfBT$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                (0, $43a3b93638fe5db9$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef
                }
            ],
            [
                (0, $41fb335299a4a39e$export$37fb8590cf2c088c),
                {
                    ...inputProps,
                    ref: inputOrTextAreaRef
                }
            ],
            [
                (0, $bd263d78e9bf3c56$export$2dc6166a7e65358c),
                {
                    ...inputProps,
                    ref: inputOrTextAreaRef
                }
            ],
            [
                (0, $3a442827418ebe87$export$f9c6924e160136d1),
                {
                    role: 'presentation',
                    isInvalid: validation.isInvalid,
                    isDisabled: props.isDisabled || false
                }
            ],
            [
                (0, $efe09c6d1c304b50$export$9afb8bc826b033ea),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $1f3c3b1a70cec653$export$ff05c3ac10437e03),
                validation
            ]
        ]
    }, renderProps.children));
});


export {$b8dcdc58eeae0d40$export$2129e27b3ef0d483 as TextFieldContext, $b8dcdc58eeae0d40$export$2c73285ae9390cec as TextField};
//# sourceMappingURL=TextField.mjs.map
