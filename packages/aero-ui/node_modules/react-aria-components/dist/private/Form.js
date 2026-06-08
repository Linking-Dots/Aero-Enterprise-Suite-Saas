import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415} from "./utils.js";
import {FormValidationContext as $ks3WP$FormValidationContext} from "react-stately/private/form/useFormValidationState";
import $ks3WP$react, {createContext as $ks3WP$createContext, forwardRef as $ks3WP$forwardRef} from "react";

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


const $c7332d4a2d191cd2$export$c24727297075ec6a = /*#__PURE__*/ (0, $ks3WP$createContext)(null);
const $c7332d4a2d191cd2$export$a7fed597f4b8afd8 = /*#__PURE__*/ (0, $ks3WP$forwardRef)(function Form(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $c7332d4a2d191cd2$export$c24727297075ec6a);
    let { validationErrors: validationErrors, validationBehavior: validationBehavior = 'native', children: children, className: className, ...domProps } = props;
    return /*#__PURE__*/ (0, $ks3WP$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).form, {
        noValidate: validationBehavior !== 'native',
        ...domProps,
        ref: ref,
        className: className || 'react-aria-Form'
    }, /*#__PURE__*/ (0, $ks3WP$react).createElement($c7332d4a2d191cd2$export$c24727297075ec6a.Provider, {
        value: {
            ...props,
            validationBehavior: validationBehavior
        }
    }, /*#__PURE__*/ (0, $ks3WP$react).createElement((0, $ks3WP$FormValidationContext).Provider, {
        value: validationErrors !== null && validationErrors !== void 0 ? validationErrors : {}
    }, children)));
});


export {$c7332d4a2d191cd2$export$c24727297075ec6a as FormContext, $c7332d4a2d191cd2$export$a7fed597f4b8afd8 as Form};
//# sourceMappingURL=Form.js.map
