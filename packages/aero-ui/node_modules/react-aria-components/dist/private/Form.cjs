var $048d76b84370f141$exports = require("./utils.cjs");
var $9WjdY$reactstatelyprivateformuseFormValidationState = require("react-stately/private/form/useFormValidationState");
var $9WjdY$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "FormContext", function () { return $5adc12e2ce73be8f$export$c24727297075ec6a; });
$parcel$export(module.exports, "Form", function () { return $5adc12e2ce73be8f$export$a7fed597f4b8afd8; });
/*
 * Copyright 2023 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the 'License');
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an 'AS IS' BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 


const $5adc12e2ce73be8f$export$c24727297075ec6a = /*#__PURE__*/ (0, $9WjdY$react.createContext)(null);
const $5adc12e2ce73be8f$export$a7fed597f4b8afd8 = /*#__PURE__*/ (0, $9WjdY$react.forwardRef)(function Form(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $5adc12e2ce73be8f$export$c24727297075ec6a);
    let { validationErrors: validationErrors, validationBehavior: validationBehavior = 'native', children: children, className: className, ...domProps } = props;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($9WjdY$react))).createElement((0, $048d76b84370f141$exports.dom).form, {
        noValidate: validationBehavior !== 'native',
        ...domProps,
        ref: ref,
        className: className || 'react-aria-Form'
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9WjdY$react))).createElement($5adc12e2ce73be8f$export$c24727297075ec6a.Provider, {
        value: {
            ...props,
            validationBehavior: validationBehavior
        }
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9WjdY$react))).createElement((0, $9WjdY$reactstatelyprivateformuseFormValidationState.FormValidationContext).Provider, {
        value: validationErrors ?? {}
    }, children)));
});


//# sourceMappingURL=Form.cjs.map
