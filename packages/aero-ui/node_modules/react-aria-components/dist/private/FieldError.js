import {useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3} from "./utils.js";
import {Text as $20d769b1e2b13352$export$5f1af8db9871e1d6} from "./Text.js";
import {filterDOMProps as $n0ShN$filterDOMProps} from "react-aria/filterDOMProps";
import $n0ShN$react, {createContext as $n0ShN$createContext, forwardRef as $n0ShN$forwardRef, useContext as $n0ShN$useContext} from "react";

/*
 * Copyright 2023 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 



const $6567560e1d9cc847$export$ff05c3ac10437e03 = /*#__PURE__*/ (0, $n0ShN$createContext)(null);
const $6567560e1d9cc847$export$f551688fc98f2e09 = /*#__PURE__*/ (0, $n0ShN$forwardRef)(function FieldError(props, ref) {
    let validation = (0, $n0ShN$useContext)($6567560e1d9cc847$export$ff05c3ac10437e03);
    if (!(validation === null || validation === void 0 ? void 0 : validation.isInvalid)) return null;
    return /*#__PURE__*/ (0, $n0ShN$react).createElement($6567560e1d9cc847$var$FieldErrorInner, {
        ...props,
        ref: ref
    });
});
const $6567560e1d9cc847$var$FieldErrorInner = /*#__PURE__*/ (0, $n0ShN$forwardRef)((props, ref)=>{
    let validation = (0, $n0ShN$useContext)($6567560e1d9cc847$export$ff05c3ac10437e03);
    let { elementType: elementType, ...restProps } = props;
    let domProps = (0, $n0ShN$filterDOMProps)(restProps, {
        global: true
    });
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...restProps,
        defaultClassName: 'react-aria-FieldError',
        defaultChildren: validation.validationErrors.length === 0 ? undefined : validation.validationErrors.join(' '),
        values: validation
    });
    if (renderProps.children == null) return null;
    return /*#__PURE__*/ (0, $n0ShN$react).createElement((0, $20d769b1e2b13352$export$5f1af8db9871e1d6), {
        slot: "errorMessage",
        elementType: elementType,
        ...domProps,
        ...renderProps,
        ref: ref
    });
});


export {$6567560e1d9cc847$export$ff05c3ac10437e03 as FieldErrorContext, $6567560e1d9cc847$export$f551688fc98f2e09 as FieldError};
//# sourceMappingURL=FieldError.js.map
