import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415} from "./utils.mjs";
import {FormValidationContext as $iSTzu$FormValidationContext} from "react-stately/private/form/useFormValidationState";
import $iSTzu$react, {createContext as $iSTzu$createContext, forwardRef as $iSTzu$forwardRef} from "react";

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


const $cdaed739b1139372$export$c24727297075ec6a = /*#__PURE__*/ (0, $iSTzu$createContext)(null);
const $cdaed739b1139372$export$a7fed597f4b8afd8 = /*#__PURE__*/ (0, $iSTzu$forwardRef)(function Form(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $cdaed739b1139372$export$c24727297075ec6a);
    let { validationErrors: validationErrors, validationBehavior: validationBehavior = 'native', children: children, className: className, ...domProps } = props;
    return /*#__PURE__*/ (0, $iSTzu$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).form, {
        noValidate: validationBehavior !== 'native',
        ...domProps,
        ref: ref,
        className: className || 'react-aria-Form'
    }, /*#__PURE__*/ (0, $iSTzu$react).createElement($cdaed739b1139372$export$c24727297075ec6a.Provider, {
        value: {
            ...props,
            validationBehavior: validationBehavior
        }
    }, /*#__PURE__*/ (0, $iSTzu$react).createElement((0, $iSTzu$FormValidationContext).Provider, {
        value: validationErrors ?? {}
    }, children)));
});


export {$cdaed739b1139372$export$c24727297075ec6a as FormContext, $cdaed739b1139372$export$a7fed597f4b8afd8 as Form};
//# sourceMappingURL=Form.mjs.map
