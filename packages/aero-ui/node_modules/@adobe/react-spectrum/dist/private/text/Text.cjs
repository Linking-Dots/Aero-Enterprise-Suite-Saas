var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $dXVMJ$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $dXVMJ$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Text", function () { return $15e3b68ec42125a9$export$5f1af8db9871e1d6; });
/*
 * Copyright 2020 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 




const $15e3b68ec42125a9$export$5f1af8db9871e1d6 = /*#__PURE__*/ (0, $dXVMJ$react.forwardRef)(function Text(props, ref) {
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'text');
    let { children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($dXVMJ$react))).createElement("span", {
        role: "none",
        ...(0, $dXVMJ$reactariafilterDOMProps.filterDOMProps)(otherProps),
        ...styleProps,
        ref: domRef
    }, children);
});


//# sourceMappingURL=Text.cjs.map
