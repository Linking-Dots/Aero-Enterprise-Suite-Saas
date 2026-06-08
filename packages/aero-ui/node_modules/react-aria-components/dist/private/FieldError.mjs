import {useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {Text as $efe09c6d1c304b50$export$5f1af8db9871e1d6} from "./Text.mjs";
import {filterDOMProps as $QpkXT$filterDOMProps} from "react-aria/filterDOMProps";
import $QpkXT$react, {createContext as $QpkXT$createContext, forwardRef as $QpkXT$forwardRef, useContext as $QpkXT$useContext} from "react";

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



const $1f3c3b1a70cec653$export$ff05c3ac10437e03 = /*#__PURE__*/ (0, $QpkXT$createContext)(null);
const $1f3c3b1a70cec653$export$f551688fc98f2e09 = /*#__PURE__*/ (0, $QpkXT$forwardRef)(function FieldError(props, ref) {
    let validation = (0, $QpkXT$useContext)($1f3c3b1a70cec653$export$ff05c3ac10437e03);
    if (!validation?.isInvalid) return null;
    return /*#__PURE__*/ (0, $QpkXT$react).createElement($1f3c3b1a70cec653$var$FieldErrorInner, {
        ...props,
        ref: ref
    });
});
const $1f3c3b1a70cec653$var$FieldErrorInner = /*#__PURE__*/ (0, $QpkXT$forwardRef)((props, ref)=>{
    let validation = (0, $QpkXT$useContext)($1f3c3b1a70cec653$export$ff05c3ac10437e03);
    let { elementType: elementType, ...restProps } = props;
    let domProps = (0, $QpkXT$filterDOMProps)(restProps, {
        global: true
    });
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...restProps,
        defaultClassName: 'react-aria-FieldError',
        defaultChildren: validation.validationErrors.length === 0 ? undefined : validation.validationErrors.join(' '),
        values: validation
    });
    if (renderProps.children == null) return null;
    return /*#__PURE__*/ (0, $QpkXT$react).createElement((0, $efe09c6d1c304b50$export$5f1af8db9871e1d6), {
        slot: "errorMessage",
        elementType: elementType,
        ...domProps,
        ...renderProps,
        ref: ref
    });
});


export {$1f3c3b1a70cec653$export$ff05c3ac10437e03 as FieldErrorContext, $1f3c3b1a70cec653$export$f551688fc98f2e09 as FieldError};
//# sourceMappingURL=FieldError.mjs.map
