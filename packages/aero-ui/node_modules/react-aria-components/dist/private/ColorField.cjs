var $048d76b84370f141$exports = require("./utils.cjs");
var $862aa7df04d8fa76$exports = require("./FieldError.cjs");
var $f3068c15cd7dcac2$exports = require("./Group.cjs");
var $81dc1c05bf045ce0$exports = require("./Input.cjs");
var $d5d46822336ca1e1$exports = require("./Label.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $2PCMI$reactariauseColorField = require("react-aria/useColorField");
var $2PCMI$reactstatelyuseColorFieldState = require("react-stately/useColorFieldState");
var $2PCMI$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $2PCMI$react = require("react");
var $2PCMI$reactariaI18nProvider = require("react-aria/I18nProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ColorFieldContext", function () { return $84f5a74f9beac008$export$44644b8a16031b5b; });
$parcel$export(module.exports, "ColorFieldStateContext", function () { return $84f5a74f9beac008$export$96b6d32b05a1a8ed; });
$parcel$export(module.exports, "ColorField", function () { return $84f5a74f9beac008$export$b865d4358897bb17; });
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










const $84f5a74f9beac008$export$44644b8a16031b5b = /*#__PURE__*/ (0, $2PCMI$react.createContext)(null);
const $84f5a74f9beac008$export$96b6d32b05a1a8ed = /*#__PURE__*/ (0, $2PCMI$react.createContext)(null);
const $84f5a74f9beac008$export$b865d4358897bb17 = /*#__PURE__*/ (0, $2PCMI$react.forwardRef)(function ColorField(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $84f5a74f9beac008$export$44644b8a16031b5b);
    if (props.channel) return /*#__PURE__*/ (0, ($parcel$interopDefault($2PCMI$react))).createElement($84f5a74f9beac008$var$ColorChannelField, {
        ...props,
        channel: props.channel,
        forwardedRef: ref
    });
    else return /*#__PURE__*/ (0, ($parcel$interopDefault($2PCMI$react))).createElement($84f5a74f9beac008$var$HexColorField, {
        ...props,
        forwardedRef: ref
    });
});
function $84f5a74f9beac008$var$ColorChannelField(props) {
    let { locale: locale } = (0, $2PCMI$reactariaI18nProvider.useLocale)();
    let state = (0, $2PCMI$reactstatelyuseColorFieldState.useColorChannelFieldState)({
        ...props,
        locale: locale
    });
    let inputRef = (0, $2PCMI$react.useRef)(null);
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let { labelProps: labelProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $2PCMI$reactariauseColorField.useColorChannelField)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        label: label,
        validationBehavior: props.validationBehavior ?? 'native'
    }, state, inputRef);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($2PCMI$react))).createElement((0, ($parcel$interopDefault($2PCMI$react))).Fragment, null, $84f5a74f9beac008$var$useChildren(props, state, props.forwardedRef, inputProps, inputRef, labelProps, labelRef, descriptionProps, errorMessageProps, validation), props.name && /*#__PURE__*/ (0, ($parcel$interopDefault($2PCMI$react))).createElement("input", {
        type: "hidden",
        name: props.name,
        form: props.form,
        value: isNaN(state.numberValue) ? '' : state.numberValue
    }));
}
function $84f5a74f9beac008$var$HexColorField(props) {
    let state = (0, $2PCMI$reactstatelyuseColorFieldState.useColorFieldState)({
        ...props,
        validationBehavior: props.validationBehavior ?? 'native'
    });
    let inputRef = (0, $2PCMI$react.useRef)(null);
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let { labelProps: labelProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $2PCMI$reactariauseColorField.useColorField)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        label: label,
        validationBehavior: props.validationBehavior ?? 'native'
    }, state, inputRef);
    return $84f5a74f9beac008$var$useChildren(props, state, props.forwardedRef, inputProps, inputRef, labelProps, labelRef, descriptionProps, errorMessageProps, validation);
}
function $84f5a74f9beac008$var$useChildren(props, state, ref, inputProps, inputRef, labelProps, labelRef, descriptionProps, errorMessageProps, validation) {
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $2PCMI$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($2PCMI$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $84f5a74f9beac008$export$96b6d32b05a1a8ed,
                state
            ],
            [
                (0, $81dc1c05bf045ce0$exports.InputContext),
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                (0, $d5d46822336ca1e1$exports.LabelContext),
                {
                    ...labelProps,
                    ref: labelRef
                }
            ],
            [
                (0, $f3068c15cd7dcac2$exports.GroupContext),
                {
                    role: 'presentation',
                    isInvalid: validation.isInvalid,
                    isDisabled: props.isDisabled || false
                }
            ],
            [
                (0, $cab7d9a238d19c33$exports.TextContext),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $862aa7df04d8fa76$exports.FieldErrorContext),
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($2PCMI$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
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


//# sourceMappingURL=ColorField.cjs.map
