var $16c7f9b22cce3838$exports = require("./Button.cjs");
var $048d76b84370f141$exports = require("./utils.cjs");
var $862aa7df04d8fa76$exports = require("./FieldError.cjs");
var $5adc12e2ce73be8f$exports = require("./Form.cjs");
var $f3068c15cd7dcac2$exports = require("./Group.cjs");
var $81dc1c05bf045ce0$exports = require("./Input.cjs");
var $d5d46822336ca1e1$exports = require("./Label.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $bFatb$reactariauseNumberField = require("react-aria/useNumberField");
var $bFatb$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $bFatb$reactstatelyuseNumberFieldState = require("react-stately/useNumberFieldState");
var $bFatb$react = require("react");
var $bFatb$reactariaI18nProvider = require("react-aria/I18nProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "NumberFieldContext", function () { return $4a2a97c8d8d8845a$export$b414a48cf5dcbc11; });
$parcel$export(module.exports, "NumberFieldStateContext", function () { return $4a2a97c8d8d8845a$export$6cc906c6cff9bec5; });
$parcel$export(module.exports, "NumberField", function () { return $4a2a97c8d8d8845a$export$63c5fa0b2fdccd2e; });
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












const $4a2a97c8d8d8845a$export$b414a48cf5dcbc11 = /*#__PURE__*/ (0, $bFatb$react.createContext)(null);
const $4a2a97c8d8d8845a$export$6cc906c6cff9bec5 = /*#__PURE__*/ (0, $bFatb$react.createContext)(null);
const $4a2a97c8d8d8845a$export$63c5fa0b2fdccd2e = /*#__PURE__*/ (0, $bFatb$react.forwardRef)(function NumberField(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $4a2a97c8d8d8845a$export$b414a48cf5dcbc11);
    let { validationBehavior: formValidationBehavior } = (0, $048d76b84370f141$exports.useSlottedContext)((0, $5adc12e2ce73be8f$exports.FormContext)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let { locale: locale } = (0, $bFatb$reactariaI18nProvider.useLocale)();
    let state = (0, $bFatb$reactstatelyuseNumberFieldState.useNumberFieldState)({
        ...props,
        locale: locale,
        validationBehavior: validationBehavior
    });
    let inputRef = (0, $bFatb$react.useRef)(null);
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let { labelProps: labelProps, groupProps: groupProps, inputProps: inputProps, incrementButtonProps: incrementButtonProps, decrementButtonProps: decrementButtonProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $bFatb$reactariauseNumberField.useNumberField)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, inputRef);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $bFatb$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($bFatb$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $4a2a97c8d8d8845a$export$6cc906c6cff9bec5,
                state
            ],
            [
                (0, $f3068c15cd7dcac2$exports.GroupContext),
                groupProps
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
                (0, $16c7f9b22cce3838$exports.ButtonContext),
                {
                    slots: {
                        increment: incrementButtonProps,
                        decrement: decrementButtonProps
                    }
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
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($bFatb$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-readonly": props.isReadOnly || undefined,
        "data-required": props.isRequired || undefined,
        "data-invalid": validation.isInvalid || undefined
    }), props.name && /*#__PURE__*/ (0, ($parcel$interopDefault($bFatb$react))).createElement("input", {
        type: "hidden",
        name: props.name,
        form: props.form,
        value: isNaN(state.numberValue) ? '' : state.numberValue,
        disabled: props.isDisabled || undefined
    }));
});


//# sourceMappingURL=NumberField.cjs.map
