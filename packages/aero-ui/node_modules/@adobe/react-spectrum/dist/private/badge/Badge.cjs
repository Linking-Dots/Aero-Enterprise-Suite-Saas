var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
require("../badge_vars.css");
var $ea784284eaa55e6e$exports = require("../badge_vars_css.cjs");
var $15e3b68ec42125a9$exports = require("../text/Text.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $hF1p1$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $hF1p1$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Badge", function () { return $ffd39385cd1921fa$export$37acb3580601e69a; });
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








const $ffd39385cd1921fa$export$37acb3580601e69a = /*#__PURE__*/ (0, $hF1p1$react.forwardRef)(function Badge(props, ref) {
    let { children: children, variant: variant, ...otherProps } = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let isTextOnly = (0, ($parcel$interopDefault($hF1p1$react))).Children.toArray(props.children).every((c)=>!/*#__PURE__*/ (0, ($parcel$interopDefault($hF1p1$react))).isValidElement(c));
    return /*#__PURE__*/ (0, ($parcel$interopDefault($hF1p1$react))).createElement("span", {
        ...(0, $hF1p1$reactariafilterDOMProps.filterDOMProps)(otherProps),
        ...styleProps,
        role: "presentation",
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($ea784284eaa55e6e$exports))), 'spectrum-Badge', {
            [`spectrum-Badge--${variant}`]: variant
        }, styleProps.className),
        ref: domRef
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($hF1p1$react))).createElement((0, $feede71cddc0c5f3$exports.ClearSlots), null, /*#__PURE__*/ (0, ($parcel$interopDefault($hF1p1$react))).createElement((0, $feede71cddc0c5f3$exports.SlotProvider), {
        slots: {
            icon: {
                size: 'S',
                UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($ea784284eaa55e6e$exports))), 'spectrum-Badge-icon')
            },
            text: {
                UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($ea784284eaa55e6e$exports))), 'spectrum-Badge-label')
            }
        }
    }, typeof children === 'string' || isTextOnly ? /*#__PURE__*/ (0, ($parcel$interopDefault($hF1p1$react))).createElement((0, $15e3b68ec42125a9$exports.Text), null, children) : children)));
});


//# sourceMappingURL=Badge.cjs.map
