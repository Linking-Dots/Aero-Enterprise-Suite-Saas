var $048d76b84370f141$exports = require("./utils.cjs");
var $9a60bd90621ebc78$exports = require("./SharedElementTransition.cjs");
var $9eZ4Z$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "SelectionIndicatorContext", function () { return $61557b2a9b2862a8$export$c9549807523555e0; });
$parcel$export(module.exports, "SelectionIndicator", function () { return $61557b2a9b2862a8$export$17f80983afe4e444; });
/*
 * Copyright 2025 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 


const $61557b2a9b2862a8$export$c9549807523555e0 = /*#__PURE__*/ (0, $9eZ4Z$react.createContext)({
    isSelected: false
});
const $61557b2a9b2862a8$export$17f80983afe4e444 = /*#__PURE__*/ (0, $9eZ4Z$react.forwardRef)(function SelectionIndicator(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $61557b2a9b2862a8$export$c9549807523555e0);
    let { isSelected: isSelected, ...otherProps } = props;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($9eZ4Z$react))).createElement((0, $9a60bd90621ebc78$exports.SharedElement), {
        ...otherProps,
        ref: ref,
        className: props.className || 'react-aria-SelectionIndicator',
        name: "SelectionIndicator",
        isVisible: isSelected
    });
});


//# sourceMappingURL=SelectionIndicator.cjs.map
