import {ButtonContext as $7705c033048f6da7$export$24d547caef80ccd1} from "./Button.mjs";
import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, removeDataAttributes as $7230ffa83bc0c2cf$export$ef03459518577ad4, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlot as $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {FieldErrorContext as $1f3c3b1a70cec653$export$ff05c3ac10437e03} from "./FieldError.mjs";
import {FormContext as $cdaed739b1139372$export$c24727297075ec6a} from "./Form.mjs";
import {GroupContext as $3a442827418ebe87$export$f9c6924e160136d1} from "./Group.mjs";
import {InputContext as $41fb335299a4a39e$export$37fb8590cf2c088c} from "./Input.mjs";
import {LabelContext as $43a3b93638fe5db9$export$75b6ee27786ba447} from "./Label.mjs";
import {TextContext as $efe09c6d1c304b50$export$9afb8bc826b033ea} from "./Text.mjs";
import {useNumberField as $kf26I$useNumberField} from "react-aria/useNumberField";
import {filterDOMProps as $kf26I$filterDOMProps} from "react-aria/filterDOMProps";
import {useNumberFieldState as $kf26I$useNumberFieldState} from "react-stately/useNumberFieldState";
import $kf26I$react, {createContext as $kf26I$createContext, forwardRef as $kf26I$forwardRef, useRef as $kf26I$useRef} from "react";
import {useLocale as $kf26I$useLocale} from "react-aria/I18nProvider";

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












const $10076bcc592bc574$export$b414a48cf5dcbc11 = /*#__PURE__*/ (0, $kf26I$createContext)(null);
const $10076bcc592bc574$export$6cc906c6cff9bec5 = /*#__PURE__*/ (0, $kf26I$createContext)(null);
const $10076bcc592bc574$export$63c5fa0b2fdccd2e = /*#__PURE__*/ (0, $kf26I$forwardRef)(function NumberField(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $10076bcc592bc574$export$b414a48cf5dcbc11);
    let { validationBehavior: formValidationBehavior } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)((0, $cdaed739b1139372$export$c24727297075ec6a)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let { locale: locale } = (0, $kf26I$useLocale)();
    let state = (0, $kf26I$useNumberFieldState)({
        ...props,
        locale: locale,
        validationBehavior: validationBehavior
    });
    let inputRef = (0, $kf26I$useRef)(null);
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { labelProps: labelProps, groupProps: groupProps, inputProps: inputProps, incrementButtonProps: incrementButtonProps, decrementButtonProps: decrementButtonProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $kf26I$useNumberField)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, inputRef);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            state: state,
            isDisabled: props.isDisabled || false,
            isInvalid: validation.isInvalid || false,
            isRequired: props.isRequired || false,
            isReadOnly: props.isReadOnly || false
        },
        defaultClassName: 'react-aria-NumberField'
    });
    let DOMProps = (0, $kf26I$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $kf26I$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $10076bcc592bc574$export$6cc906c6cff9bec5,
                state
            ],
            [
                (0, $3a442827418ebe87$export$f9c6924e160136d1),
                groupProps
            ],
            [
                (0, $41fb335299a4a39e$export$37fb8590cf2c088c),
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                (0, $43a3b93638fe5db9$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef
                }
            ],
            [
                (0, $7705c033048f6da7$export$24d547caef80ccd1),
                {
                    slots: {
                        increment: incrementButtonProps,
                        decrement: decrementButtonProps
                    }
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
    }, /*#__PURE__*/ (0, $kf26I$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-readonly": props.isReadOnly || undefined,
        "data-required": props.isRequired || undefined,
        "data-invalid": validation.isInvalid || undefined
    }), props.name && /*#__PURE__*/ (0, $kf26I$react).createElement("input", {
        type: "hidden",
        name: props.name,
        form: props.form,
        value: isNaN(state.numberValue) ? '' : state.numberValue,
        disabled: props.isDisabled || undefined
    }));
});


export {$10076bcc592bc574$export$b414a48cf5dcbc11 as NumberFieldContext, $10076bcc592bc574$export$6cc906c6cff9bec5 as NumberFieldStateContext, $10076bcc592bc574$export$63c5fa0b2fdccd2e as NumberField};
//# sourceMappingURL=NumberField.mjs.map
