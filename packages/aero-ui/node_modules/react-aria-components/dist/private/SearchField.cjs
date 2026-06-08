var $16c7f9b22cce3838$exports = require("./Button.cjs");
var $048d76b84370f141$exports = require("./utils.cjs");
var $862aa7df04d8fa76$exports = require("./FieldError.cjs");
var $433949643203e332$exports = require("./Autocomplete.cjs");
var $5adc12e2ce73be8f$exports = require("./Form.cjs");
var $f3068c15cd7dcac2$exports = require("./Group.cjs");
var $81dc1c05bf045ce0$exports = require("./Input.cjs");
var $d5d46822336ca1e1$exports = require("./Label.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $lsR3k$reactariauseSearchField = require("react-aria/useSearchField");
var $lsR3k$reactariaprivatecollectionsHidden = require("react-aria/private/collections/Hidden");
var $lsR3k$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $lsR3k$react = require("react");
var $lsR3k$reactstatelyuseSearchFieldState = require("react-stately/useSearchFieldState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "SearchFieldContext", function () { return $a874760b9ba032eb$export$d1c4e4c63cb03a11; });
$parcel$export(module.exports, "SearchField", function () { return $a874760b9ba032eb$export$b94867ecbd698f21; });
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













const $a874760b9ba032eb$export$d1c4e4c63cb03a11 = /*#__PURE__*/ (0, $lsR3k$react.createContext)(null);
const $a874760b9ba032eb$export$b94867ecbd698f21 = /*#__PURE__*/ (0, $lsR3k$reactariaprivatecollectionsHidden.createHideableComponent)(function SearchField(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $a874760b9ba032eb$export$d1c4e4c63cb03a11);
    let { validationBehavior: formValidationBehavior } = (0, $048d76b84370f141$exports.useSlottedContext)((0, $5adc12e2ce73be8f$exports.FormContext)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let inputRef = (0, $lsR3k$react.useRef)(null);
    [props, inputRef] = (0, $048d76b84370f141$exports.useContextProps)(props, inputRef, (0, $433949643203e332$exports.FieldInputContext));
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let state = (0, $lsR3k$reactstatelyuseSearchFieldState.useSearchFieldState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let { labelProps: labelProps, inputProps: inputProps, clearButtonProps: clearButtonProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $lsR3k$reactariauseSearchField.useSearchField)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, inputRef);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: {
            isEmpty: state.value === '',
            isDisabled: props.isDisabled || false,
            isInvalid: validation.isInvalid || false,
            isReadOnly: props.isReadOnly || false,
            isRequired: props.isRequired || false,
            state: state
        },
        defaultClassName: 'react-aria-SearchField'
    });
    let DOMProps = (0, $lsR3k$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($lsR3k$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-empty": state.value === '' || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": validation.isInvalid || undefined,
        "data-readonly": props.isReadOnly || undefined,
        "data-required": props.isRequired || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($lsR3k$react))).createElement((0, $048d76b84370f141$exports.Provider), {
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
                    ref: inputRef
                }
            ],
            [
                (0, $16c7f9b22cce3838$exports.ButtonContext),
                clearButtonProps
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
                (0, $f3068c15cd7dcac2$exports.GroupContext),
                {
                    isInvalid: validation.isInvalid,
                    isDisabled: props.isDisabled || false
                }
            ],
            [
                (0, $862aa7df04d8fa76$exports.FieldErrorContext),
                validation
            ]
        ]
    }, renderProps.children));
});


//# sourceMappingURL=SearchField.cjs.map
