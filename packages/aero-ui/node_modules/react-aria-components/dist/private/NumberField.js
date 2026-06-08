import {ButtonContext as $fc203795b9b363cd$export$24d547caef80ccd1} from "./Button.js";
import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, removeDataAttributes as $b7b7a92703138c9b$export$ef03459518577ad4, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3, useSlot as $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8, useSlottedContext as $b7b7a92703138c9b$export$fabf2dc03a41866e} from "./utils.js";
import {FieldErrorContext as $6567560e1d9cc847$export$ff05c3ac10437e03} from "./FieldError.js";
import {FormContext as $c7332d4a2d191cd2$export$c24727297075ec6a} from "./Form.js";
import {GroupContext as $2e357e4f16c05be6$export$f9c6924e160136d1} from "./Group.js";
import {InputContext as $d8e7992b5f7739ce$export$37fb8590cf2c088c} from "./Input.js";
import {LabelContext as $3e4839e5b30e7b17$export$75b6ee27786ba447} from "./Label.js";
import {TextContext as $20d769b1e2b13352$export$9afb8bc826b033ea} from "./Text.js";
import {useNumberField as $3MIOf$useNumberField} from "react-aria/useNumberField";
import {filterDOMProps as $3MIOf$filterDOMProps} from "react-aria/filterDOMProps";
import {useNumberFieldState as $3MIOf$useNumberFieldState} from "react-stately/useNumberFieldState";
import $3MIOf$react, {createContext as $3MIOf$createContext, forwardRef as $3MIOf$forwardRef, useRef as $3MIOf$useRef} from "react";
import {useLocale as $3MIOf$useLocale} from "react-aria/I18nProvider";

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












const $bc465c0b918d6915$export$b414a48cf5dcbc11 = /*#__PURE__*/ (0, $3MIOf$createContext)(null);
const $bc465c0b918d6915$export$6cc906c6cff9bec5 = /*#__PURE__*/ (0, $3MIOf$createContext)(null);
const $bc465c0b918d6915$export$63c5fa0b2fdccd2e = /*#__PURE__*/ (0, $3MIOf$forwardRef)(function NumberField(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $bc465c0b918d6915$export$b414a48cf5dcbc11);
    let { validationBehavior: formValidationBehavior } = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)((0, $c7332d4a2d191cd2$export$c24727297075ec6a)) || {};
    var _props_validationBehavior, _ref;
    let validationBehavior = (_ref = (_props_validationBehavior = props.validationBehavior) !== null && _props_validationBehavior !== void 0 ? _props_validationBehavior : formValidationBehavior) !== null && _ref !== void 0 ? _ref : 'native';
    let { locale: locale } = (0, $3MIOf$useLocale)();
    let state = (0, $3MIOf$useNumberFieldState)({
        ...props,
        locale: locale,
        validationBehavior: validationBehavior
    });
    let inputRef = (0, $3MIOf$useRef)(null);
    let [labelRef, label] = (0, $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { labelProps: labelProps, groupProps: groupProps, inputProps: inputProps, incrementButtonProps: incrementButtonProps, decrementButtonProps: decrementButtonProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $3MIOf$useNumberField)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, inputRef);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $3MIOf$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $3MIOf$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $bc465c0b918d6915$export$6cc906c6cff9bec5,
                state
            ],
            [
                (0, $2e357e4f16c05be6$export$f9c6924e160136d1),
                groupProps
            ],
            [
                (0, $d8e7992b5f7739ce$export$37fb8590cf2c088c),
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                (0, $3e4839e5b30e7b17$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef
                }
            ],
            [
                (0, $fc203795b9b363cd$export$24d547caef80ccd1),
                {
                    slots: {
                        increment: incrementButtonProps,
                        decrement: decrementButtonProps
                    }
                }
            ],
            [
                (0, $20d769b1e2b13352$export$9afb8bc826b033ea),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $6567560e1d9cc847$export$ff05c3ac10437e03),
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, $3MIOf$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-readonly": props.isReadOnly || undefined,
        "data-required": props.isRequired || undefined,
        "data-invalid": validation.isInvalid || undefined
    }), props.name && /*#__PURE__*/ (0, $3MIOf$react).createElement("input", {
        type: "hidden",
        name: props.name,
        form: props.form,
        value: isNaN(state.numberValue) ? '' : state.numberValue,
        disabled: props.isDisabled || undefined
    }));
});


export {$bc465c0b918d6915$export$b414a48cf5dcbc11 as NumberFieldContext, $bc465c0b918d6915$export$6cc906c6cff9bec5 as NumberFieldStateContext, $bc465c0b918d6915$export$63c5fa0b2fdccd2e as NumberField};
//# sourceMappingURL=NumberField.js.map
