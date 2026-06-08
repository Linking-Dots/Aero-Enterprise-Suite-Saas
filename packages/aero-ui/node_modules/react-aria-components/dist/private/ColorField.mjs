import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, removeDataAttributes as $7230ffa83bc0c2cf$export$ef03459518577ad4, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlot as $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8} from "./utils.mjs";
import {FieldErrorContext as $1f3c3b1a70cec653$export$ff05c3ac10437e03} from "./FieldError.mjs";
import {GroupContext as $3a442827418ebe87$export$f9c6924e160136d1} from "./Group.mjs";
import {InputContext as $41fb335299a4a39e$export$37fb8590cf2c088c} from "./Input.mjs";
import {LabelContext as $43a3b93638fe5db9$export$75b6ee27786ba447} from "./Label.mjs";
import {TextContext as $efe09c6d1c304b50$export$9afb8bc826b033ea} from "./Text.mjs";
import {useColorChannelField as $2BvAu$useColorChannelField, useColorField as $2BvAu$useColorField} from "react-aria/useColorField";
import {useColorChannelFieldState as $2BvAu$useColorChannelFieldState, useColorFieldState as $2BvAu$useColorFieldState} from "react-stately/useColorFieldState";
import {filterDOMProps as $2BvAu$filterDOMProps} from "react-aria/filterDOMProps";
import $2BvAu$react, {createContext as $2BvAu$createContext, forwardRef as $2BvAu$forwardRef, useRef as $2BvAu$useRef} from "react";
import {useLocale as $2BvAu$useLocale} from "react-aria/I18nProvider";

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










const $baf01eb6a6ce4d91$export$44644b8a16031b5b = /*#__PURE__*/ (0, $2BvAu$createContext)(null);
const $baf01eb6a6ce4d91$export$96b6d32b05a1a8ed = /*#__PURE__*/ (0, $2BvAu$createContext)(null);
const $baf01eb6a6ce4d91$export$b865d4358897bb17 = /*#__PURE__*/ (0, $2BvAu$forwardRef)(function ColorField(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $baf01eb6a6ce4d91$export$44644b8a16031b5b);
    if (props.channel) return /*#__PURE__*/ (0, $2BvAu$react).createElement($baf01eb6a6ce4d91$var$ColorChannelField, {
        ...props,
        channel: props.channel,
        forwardedRef: ref
    });
    else return /*#__PURE__*/ (0, $2BvAu$react).createElement($baf01eb6a6ce4d91$var$HexColorField, {
        ...props,
        forwardedRef: ref
    });
});
function $baf01eb6a6ce4d91$var$ColorChannelField(props) {
    let { locale: locale } = (0, $2BvAu$useLocale)();
    let state = (0, $2BvAu$useColorChannelFieldState)({
        ...props,
        locale: locale
    });
    let inputRef = (0, $2BvAu$useRef)(null);
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { labelProps: labelProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $2BvAu$useColorChannelField)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        label: label,
        validationBehavior: props.validationBehavior ?? 'native'
    }, state, inputRef);
    return /*#__PURE__*/ (0, $2BvAu$react).createElement((0, $2BvAu$react).Fragment, null, $baf01eb6a6ce4d91$var$useChildren(props, state, props.forwardedRef, inputProps, inputRef, labelProps, labelRef, descriptionProps, errorMessageProps, validation), props.name && /*#__PURE__*/ (0, $2BvAu$react).createElement("input", {
        type: "hidden",
        name: props.name,
        form: props.form,
        value: isNaN(state.numberValue) ? '' : state.numberValue
    }));
}
function $baf01eb6a6ce4d91$var$HexColorField(props) {
    let state = (0, $2BvAu$useColorFieldState)({
        ...props,
        validationBehavior: props.validationBehavior ?? 'native'
    });
    let inputRef = (0, $2BvAu$useRef)(null);
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { labelProps: labelProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $2BvAu$useColorField)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        label: label,
        validationBehavior: props.validationBehavior ?? 'native'
    }, state, inputRef);
    return $baf01eb6a6ce4d91$var$useChildren(props, state, props.forwardedRef, inputProps, inputRef, labelProps, labelRef, descriptionProps, errorMessageProps, validation);
}
function $baf01eb6a6ce4d91$var$useChildren(props, state, ref, inputProps, inputRef, labelProps, labelRef, descriptionProps, errorMessageProps, validation) {
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $2BvAu$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $2BvAu$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $baf01eb6a6ce4d91$export$96b6d32b05a1a8ed,
                state
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
    }, /*#__PURE__*/ (0, $2BvAu$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
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


export {$baf01eb6a6ce4d91$export$44644b8a16031b5b as ColorFieldContext, $baf01eb6a6ce4d91$export$96b6d32b05a1a8ed as ColorFieldStateContext, $baf01eb6a6ce4d91$export$b865d4358897bb17 as ColorField};
//# sourceMappingURL=ColorField.mjs.map
