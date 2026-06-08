var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $apiIn$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $apiIn$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Header", function () { return $0eb8881e119007c6$export$8b251419efc915eb; });
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




const $0eb8881e119007c6$export$8b251419efc915eb = /*#__PURE__*/ (0, $apiIn$react.forwardRef)(function Header(props, ref) {
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'header');
    let { children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($apiIn$react))).createElement("header", {
        ...(0, $apiIn$reactariafilterDOMProps.filterDOMProps)(otherProps),
        ...styleProps,
        ref: domRef
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($apiIn$react))).createElement((0, $feede71cddc0c5f3$exports.ClearSlots), null, children));
});


//# sourceMappingURL=Header.cjs.map
