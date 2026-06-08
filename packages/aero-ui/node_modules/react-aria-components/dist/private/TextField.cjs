var $048d76b84370f141$exports = require("./utils.cjs");
var $862aa7df04d8fa76$exports = require("./FieldError.cjs");
var $433949643203e332$exports = require("./Autocomplete.cjs");
var $5adc12e2ce73be8f$exports = require("./Form.cjs");
var $f3068c15cd7dcac2$exports = require("./Group.cjs");
var $81dc1c05bf045ce0$exports = require("./Input.cjs");
var $d5d46822336ca1e1$exports = require("./Label.cjs");
var $ad63e8449e461d5d$exports = require("./TextArea.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $mUR36$reactariauseTextField = require("react-aria/useTextField");
var $mUR36$reactariaprivatecollectionsHidden = require("react-aria/private/collections/Hidden");
var $mUR36$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $mUR36$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "TextFieldContext", function () { return $3b62da56e3335500$export$2129e27b3ef0d483; });
$parcel$export(module.exports, "TextField", function () { return $3b62da56e3335500$export$2c73285ae9390cec; });
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












const $3b62da56e3335500$export$2129e27b3ef0d483 = /*#__PURE__*/ (0, $mUR36$react.createContext)(null);
const $3b62da56e3335500$export$2c73285ae9390cec = /*#__PURE__*/ (0, $mUR36$reactariaprivatecollectionsHidden.createHideableComponent)(function TextField(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $3b62da56e3335500$export$2129e27b3ef0d483);
    let { validationBehavior: formValidationBehavior } = (0, $048d76b84370f141$exports.useSlottedContext)((0, $5adc12e2ce73be8f$exports.FormContext)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let inputRef = (0, $mUR36$react.useRef)(null);
    [props, inputRef] = (0, $048d76b84370f141$exports.useContextProps)(props, inputRef, (0, $433949643203e332$exports.FieldInputContext));
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let [inputElementType, setInputElementType] = (0, $mUR36$react.useState)('input');
    let { labelProps: labelProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $mUR36$reactariauseTextField.useTextField)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        inputElementType: inputElementType,
        label: label,
        validationBehavior: validationBehavior
    }, inputRef);
    // Intercept setting the input ref so we can determine what kind of element we have.
    // useTextField uses this to determine what props to include.
    let inputOrTextAreaRef = (0, $mUR36$react.useCallback)((el)=>{
        inputRef.current = el;
        if (el) setInputElementType(el instanceof HTMLTextAreaElement ? 'textarea' : 'input');
    }, [
        inputRef
    ]);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: {
            isDisabled: props.isDisabled || false,
            isInvalid: validation.isInvalid,
            isReadOnly: props.isReadOnly || false,
            isRequired: props.isRequired || false
        },
        defaultClassName: 'react-aria-TextField'
    });
    let DOMProps = (0, $mUR36$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($mUR36$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": validation.isInvalid || undefined,
        "data-readonly": props.isReadOnly || undefined,
        "data-required": props.isRequired || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($mUR36$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                (0, $d5d46822336ca1e1$exports.LabelContext),
                {
                    ...labelProps,
                    ref: labelRef
                }
            ],
            [
                (0, $81dc1c05bf045ce0$exports.InputContext),
                {
                    ...inputProps,
                    ref: inputOrTextAreaRef
                }
            ],
            [
                (0, $ad63e8449e461d5d$exports.TextAreaContext),
                {
                    ...inputProps,
                    ref: inputOrTextAreaRef
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
    }, renderProps.children));
});


//# sourceMappingURL=TextField.cjs.map
