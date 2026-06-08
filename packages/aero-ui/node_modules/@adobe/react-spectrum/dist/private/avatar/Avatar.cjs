var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
require("../avatar_vars.css");
var $d54d33b3db541283$exports = require("../avatar_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $afza8$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $afza8$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Avatar", function () { return $12f9bd21b5efcc62$export$e2255cf6045e8d47; });
/*
 * Copyright 2021 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 







const $12f9bd21b5efcc62$var$DEFAULT_SIZE = 'avatar-size-100';
const $12f9bd21b5efcc62$var$SIZE_RE = /^size-\d+/;
const $12f9bd21b5efcc62$export$e2255cf6045e8d47 = /*#__PURE__*/ (0, $afza8$react.forwardRef)(function Avatar(props, ref) {
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'avatar');
    const { alt: alt = '', isDisabled: isDisabled, size: size = $12f9bd21b5efcc62$var$DEFAULT_SIZE, src: src, ...otherProps } = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    const { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    const domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    const domProps = (0, $afza8$reactariafilterDOMProps.filterDOMProps)(otherProps);
    // Casting `size` as `any` since `isNaN` expects a `number`, but we want it
    // to handle `string` numbers; e.g. '300' as opposed to 300
    const sizeValue = typeof size !== 'number' && ($12f9bd21b5efcc62$var$SIZE_RE.test(size) || !isNaN(size)) ? (0, $b8f90d51c4908137$exports.dimensionValue)($12f9bd21b5efcc62$var$DEFAULT_SIZE) // override disallowed size values
     : (0, $b8f90d51c4908137$exports.dimensionValue)(size || $12f9bd21b5efcc62$var$DEFAULT_SIZE);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($afza8$react))).createElement("img", {
        ...styleProps,
        ...domProps,
        alt: alt,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d54d33b3db541283$exports))), 'spectrum-Avatar', {
            'is-disabled': isDisabled
        }, styleProps.className),
        ref: domRef,
        src: src,
        style: {
            ...styleProps.style,
            ...sizeValue && {
                height: sizeValue,
                width: sizeValue
            }
        }
    });
});


//# sourceMappingURL=Avatar.cjs.map
