var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $77647$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $77647$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Footer", function () { return $f3a05628354ee442$export$a06f1c675e846f6f; });
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




const $f3a05628354ee442$export$a06f1c675e846f6f = /*#__PURE__*/ (0, $77647$react.forwardRef)(function Footer(props, ref) {
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'footer');
    let { children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($77647$react))).createElement("footer", {
        ...(0, $77647$reactariafilterDOMProps.filterDOMProps)(otherProps),
        ...styleProps,
        ref: domRef
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($77647$react))).createElement((0, $feede71cddc0c5f3$exports.ClearSlots), null, children));
});


//# sourceMappingURL=Footer.cjs.map
