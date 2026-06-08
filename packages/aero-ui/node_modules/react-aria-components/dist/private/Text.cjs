var $048d76b84370f141$exports = require("./utils.cjs");
var $5lxjW$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "TextContext", function () { return $cab7d9a238d19c33$export$9afb8bc826b033ea; });
$parcel$export(module.exports, "Text", function () { return $cab7d9a238d19c33$export$5f1af8db9871e1d6; });
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

const $cab7d9a238d19c33$export$9afb8bc826b033ea = /*#__PURE__*/ (0, $5lxjW$react.createContext)({});
const $cab7d9a238d19c33$export$5f1af8db9871e1d6 = /*#__PURE__*/ (0, $5lxjW$react.forwardRef)(function Text(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $cab7d9a238d19c33$export$9afb8bc826b033ea);
    let { elementType: elementType = 'span', ...domProps } = props;
    let ElementType = (0, $048d76b84370f141$exports.dom)[elementType];
    // @ts-ignore
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5lxjW$react))).createElement(ElementType, {
        className: "react-aria-Text",
        ...domProps,
        ref: ref
    });
});


//# sourceMappingURL=Text.cjs.map
