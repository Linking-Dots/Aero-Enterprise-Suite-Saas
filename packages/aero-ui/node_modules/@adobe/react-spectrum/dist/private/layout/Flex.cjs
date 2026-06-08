var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
require("./flex-gap.css");
var $39129a5027937f7c$exports = require("./flex-gap_css.cjs");
var $bb33895bbbdc8bdb$exports = require("../utils/BreakpointProvider.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $8Vhfz$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $8Vhfz$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Flex", function () { return $e04035822dddb314$export$f51f4c4ede09e011; });
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






const $e04035822dddb314$var$flexStyleProps = {
    direction: [
        'flexDirection',
        (0, $b8f90d51c4908137$exports.passthroughStyle)
    ],
    wrap: [
        'flexWrap',
        $e04035822dddb314$var$flexWrapValue
    ],
    justifyContent: [
        'justifyContent',
        $e04035822dddb314$var$flexAlignValue
    ],
    alignItems: [
        'alignItems',
        $e04035822dddb314$var$flexAlignValue
    ],
    alignContent: [
        'alignContent',
        $e04035822dddb314$var$flexAlignValue
    ]
};
const $e04035822dddb314$export$f51f4c4ede09e011 = /*#__PURE__*/ (0, $8Vhfz$react.forwardRef)(function Flex(props, ref) {
    let { children: children, ...otherProps } = props;
    let breakpointProvider = (0, $bb33895bbbdc8bdb$exports.useBreakpoint)();
    let matchedBreakpoints = breakpointProvider?.matchedBreakpoints || [
        'base'
    ];
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let { styleProps: flexStyle } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps, $e04035822dddb314$var$flexStyleProps);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let style = {
        ...styleProps.style,
        ...flexStyle.style
    };
    if (props.gap != null) style.gap = (0, $b8f90d51c4908137$exports.responsiveDimensionValue)(props.gap, matchedBreakpoints);
    if (props.columnGap != null) style.columnGap = (0, $b8f90d51c4908137$exports.responsiveDimensionValue)(props.columnGap, matchedBreakpoints);
    if (props.rowGap != null) style.rowGap = (0, $b8f90d51c4908137$exports.responsiveDimensionValue)(props.rowGap, matchedBreakpoints);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8Vhfz$react))).createElement("div", {
        ...(0, $8Vhfz$reactariafilterDOMProps.filterDOMProps)(otherProps),
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($39129a5027937f7c$exports))), 'flex', styleProps.className),
        style: style,
        ref: domRef
    }, children);
});
/**
 * Normalize 'start' and 'end' alignment values to 'flex-start' and 'flex-end'
 * in flex containers for browser compatibility.
 */ function $e04035822dddb314$var$flexAlignValue(value) {
    if (value === 'start') return 'flex-start';
    if (value === 'end') return 'flex-end';
    return value;
}
/**
 * Takes a boolean and translates it to flex wrap or nowrap.
 */ function $e04035822dddb314$var$flexWrapValue(value) {
    if (typeof value === 'boolean') return value ? 'wrap' : 'nowrap';
    return value;
}


//# sourceMappingURL=Flex.cjs.map
