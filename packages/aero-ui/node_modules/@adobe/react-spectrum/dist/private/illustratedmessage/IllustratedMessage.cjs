var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $e04035822dddb314$exports = require("../layout/Flex.cjs");
require("../illustratedmessage_vars.css");
var $3b81d129d57986d7$exports = require("../illustratedmessage_vars_css.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $6fAa4$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $6fAa4$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "IllustratedMessage", function () { return $41a942f48807cdbc$export$406dbc84c317ece0; });
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






const $41a942f48807cdbc$export$406dbc84c317ece0 = /*#__PURE__*/ (0, $6fAa4$react.forwardRef)(function IllustratedMessage(props, ref) {
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'illustration');
    let { children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let headingClassName = (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($3b81d129d57986d7$exports))), 'spectrum-IllustratedMessage-heading');
    let contentClassName = (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($3b81d129d57986d7$exports))), 'spectrum-IllustratedMessage-description');
    let slots = {
        heading: {
            UNSAFE_className: headingClassName
        },
        content: {
            UNSAFE_className: contentClassName
        }
    };
    return /*#__PURE__*/ (0, ($parcel$interopDefault($6fAa4$react))).createElement((0, $e04035822dddb314$exports.Flex), {
        ...(0, $6fAa4$reactariafilterDOMProps.filterDOMProps)(otherProps),
        UNSAFE_style: styleProps.style,
        isHidden: styleProps.hidden,
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($3b81d129d57986d7$exports))), 'spectrum-IllustratedMessage', styleProps.className),
        ref: ref
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($6fAa4$react))).createElement((0, $feede71cddc0c5f3$exports.ClearSlots), null, /*#__PURE__*/ (0, ($parcel$interopDefault($6fAa4$react))).createElement((0, $feede71cddc0c5f3$exports.SlotProvider), {
        slots: slots
    }, children)));
});


//# sourceMappingURL=IllustratedMessage.cjs.map
