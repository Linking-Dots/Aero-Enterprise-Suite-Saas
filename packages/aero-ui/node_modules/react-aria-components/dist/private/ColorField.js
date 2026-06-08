import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, removeDataAttributes as $b7b7a92703138c9b$export$ef03459518577ad4, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3, useSlot as $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8} from "./utils.js";
import {FieldErrorContext as $6567560e1d9cc847$export$ff05c3ac10437e03} from "./FieldError.js";
import {GroupContext as $2e357e4f16c05be6$export$f9c6924e160136d1} from "./Group.js";
import {InputContext as $d8e7992b5f7739ce$export$37fb8590cf2c088c} from "./Input.js";
import {LabelContext as $3e4839e5b30e7b17$export$75b6ee27786ba447} from "./Label.js";
import {TextContext as $20d769b1e2b13352$export$9afb8bc826b033ea} from "./Text.js";
import {useColorChannelField as $bnIbG$useColorChannelField, useColorField as $bnIbG$useColorField} from "react-aria/useColorField";
import {useColorChannelFieldState as $bnIbG$useColorChannelFieldState, useColorFieldState as $bnIbG$useColorFieldState} from "react-stately/useColorFieldState";
import {filterDOMProps as $bnIbG$filterDOMProps} from "react-aria/filterDOMProps";
import $bnIbG$react, {createContext as $bnIbG$createContext, forwardRef as $bnIbG$forwardRef, useRef as $bnIbG$useRef} from "react";
import {useLocale as $bnIbG$useLocale} from "react-aria/I18nProvider";

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










const $6ca9ff78887ad6c3$export$44644b8a16031b5b = /*#__PURE__*/ (0, $bnIbG$createContext)(null);
const $6ca9ff78887ad6c3$export$96b6d32b05a1a8ed = /*#__PURE__*/ (0, $bnIbG$createContext)(null);
const $6ca9ff78887ad6c3$export$b865d4358897bb17 = /*#__PURE__*/ (0, $bnIbG$forwardRef)(function ColorField(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $6ca9ff78887ad6c3$export$44644b8a16031b5b);
    if (props.channel) return /*#__PURE__*/ (0, $bnIbG$react).createElement($6ca9ff78887ad6c3$var$ColorChannelField, {
        ...props,
        channel: props.channel,
        forwardedRef: ref
    });
    else return /*#__PURE__*/ (0, $bnIbG$react).createElement($6ca9ff78887ad6c3$var$HexColorField, {
        ...props,
        forwardedRef: ref
    });
});
function $6ca9ff78887ad6c3$var$ColorChannelField(props) {
    let { locale: locale } = (0, $bnIbG$useLocale)();
    let state = (0, $bnIbG$useColorChannelFieldState)({
        ...props,
        locale: locale
    });
    let inputRef = (0, $bnIbG$useRef)(null);
    let [labelRef, label] = (0, $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    var _props_validationBehavior;
    let { labelProps: labelProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $bnIbG$useColorChannelField)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        label: label,
        validationBehavior: (_props_validationBehavior = props.validationBehavior) !== null && _props_validationBehavior !== void 0 ? _props_validationBehavior : 'native'
    }, state, inputRef);
    return /*#__PURE__*/ (0, $bnIbG$react).createElement((0, $bnIbG$react).Fragment, null, $6ca9ff78887ad6c3$var$useChildren(props, state, props.forwardedRef, inputProps, inputRef, labelProps, labelRef, descriptionProps, errorMessageProps, validation), props.name && /*#__PURE__*/ (0, $bnIbG$react).createElement("input", {
        type: "hidden",
        name: props.name,
        form: props.form,
        value: isNaN(state.numberValue) ? '' : state.numberValue
    }));
}
function $6ca9ff78887ad6c3$var$HexColorField(props) {
    var _props_validationBehavior;
    let state = (0, $bnIbG$useColorFieldState)({
        ...props,
        validationBehavior: (_props_validationBehavior = props.validationBehavior) !== null && _props_validationBehavior !== void 0 ? _props_validationBehavior : 'native'
    });
    let inputRef = (0, $bnIbG$useRef)(null);
    let [labelRef, label] = (0, $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    var _props_validationBehavior1;
    let { labelProps: labelProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $bnIbG$useColorField)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        label: label,
        validationBehavior: (_props_validationBehavior1 = props.validationBehavior) !== null && _props_validationBehavior1 !== void 0 ? _props_validationBehavior1 : 'native'
    }, state, inputRef);
    return $6ca9ff78887ad6c3$var$useChildren(props, state, props.forwardedRef, inputProps, inputRef, labelProps, labelRef, descriptionProps, errorMessageProps, validation);
}
function $6ca9ff78887ad6c3$var$useChildren(props, state, ref, inputProps, inputRef, labelProps, labelRef, descriptionProps, errorMessageProps, validation) {
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        values: {
            state: state,
            channel: props.channel || 'hex',
            isDisabled: props.isDisabled || false,
            isInvalid: validation.isInvalid || false,
            isReadOnly: props.isReadOnly || false,
            isRequired: props.isRequired || false
        },
        defaultClassName: 'react-aria-ColorField'
    });
    let DOMProps = (0, $bnIbG$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $bnIbG$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $6ca9ff78887ad6c3$export$96b6d32b05a1a8ed,
                state
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
                (0, $2e357e4f16c05be6$export$f9c6924e160136d1),
                {
                    role: 'presentation',
                    isInvalid: validation.isInvalid,
                    isDisabled: props.isDisabled || false
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
    }, /*#__PURE__*/ (0, $bnIbG$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-channel": props.channel || 'hex',
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": validation.isInvalid || undefined,
        "data-readonly": props.isReadOnly || undefined,
        "data-required": props.isRequired || undefined
    }));
}


export {$6ca9ff78887ad6c3$export$44644b8a16031b5b as ColorFieldContext, $6ca9ff78887ad6c3$export$96b6d32b05a1a8ed as ColorFieldStateContext, $6ca9ff78887ad6c3$export$b865d4358897bb17 as ColorField};
//# sourceMappingURL=ColorField.js.map
