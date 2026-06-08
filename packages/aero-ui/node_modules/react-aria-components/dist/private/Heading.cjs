var $048d76b84370f141$exports = require("./utils.cjs");
var $jZYKV$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "HeadingContext", function () { return $03e8b4fd5e44cde9$export$d688439359537581; });
$parcel$export(module.exports, "Heading", function () { return $03e8b4fd5e44cde9$export$a8a3e93435678ff9; });
/*
 * Copyright 2022 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 

const $03e8b4fd5e44cde9$export$d688439359537581 = /*#__PURE__*/ (0, $jZYKV$react.createContext)({});
const $03e8b4fd5e44cde9$export$a8a3e93435678ff9 = /*#__PURE__*/ (0, $jZYKV$react.forwardRef)(function Heading(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $03e8b4fd5e44cde9$export$d688439359537581);
    let { children: children, level: level = 3, className: className, ...domProps } = props;
    let Element = (0, $048d76b84370f141$exports.dom)[`h${level}`];
    return /*#__PURE__*/ (0, ($parcel$interopDefault($jZYKV$react))).createElement(Element, {
        ...domProps,
        ref: ref,
        className: className ?? 'react-aria-Heading'
    }, children);
});


//# sourceMappingURL=Heading.cjs.map
