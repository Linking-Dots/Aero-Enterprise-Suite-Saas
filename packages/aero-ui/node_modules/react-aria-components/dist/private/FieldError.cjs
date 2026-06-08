var $048d76b84370f141$exports = require("./utils.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $bfO1g$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $bfO1g$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "FieldErrorContext", function () { return $862aa7df04d8fa76$export$ff05c3ac10437e03; });
$parcel$export(module.exports, "FieldError", function () { return $862aa7df04d8fa76$export$f551688fc98f2e09; });
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



const $862aa7df04d8fa76$export$ff05c3ac10437e03 = /*#__PURE__*/ (0, $bfO1g$react.createContext)(null);
const $862aa7df04d8fa76$export$f551688fc98f2e09 = /*#__PURE__*/ (0, $bfO1g$react.forwardRef)(function FieldError(props, ref) {
    let validation = (0, $bfO1g$react.useContext)($862aa7df04d8fa76$export$ff05c3ac10437e03);
    if (!validation?.isInvalid) return null;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($bfO1g$react))).createElement($862aa7df04d8fa76$var$FieldErrorInner, {
        ...props,
        ref: ref
    });
});
const $862aa7df04d8fa76$var$FieldErrorInner = /*#__PURE__*/ (0, $bfO1g$react.forwardRef)((props, ref)=>{
    let validation = (0, $bfO1g$react.useContext)($862aa7df04d8fa76$export$ff05c3ac10437e03);
    let { elementType: elementType, ...restProps } = props;
    let domProps = (0, $bfO1g$reactariafilterDOMProps.filterDOMProps)(restProps, {
        global: true
    });
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...restProps,
        defaultClassName: 'react-aria-FieldError',
        defaultChildren: validation.validationErrors.length === 0 ? undefined : validation.validationErrors.join(' '),
        values: validation
    });
    if (renderProps.children == null) return null;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($bfO1g$react))).createElement((0, $cab7d9a238d19c33$exports.Text), {
        slot: "errorMessage",
        elementType: elementType,
        ...domProps,
        ...renderProps,
        ref: ref
    });
});


//# sourceMappingURL=FieldError.cjs.map
