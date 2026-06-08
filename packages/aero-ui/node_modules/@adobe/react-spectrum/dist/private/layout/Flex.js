import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {passthroughStyle as $120fbea2d95e11ed$export$46b6c81d11d2c30a, responsiveDimensionValue as $120fbea2d95e11ed$export$f348bec194f2e6b5, useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import "./flex-gap.css";
import $4SSrN$flexgap_cssmjs from "./flex-gap_css.mjs";
import {useBreakpoint as $cf1a1f4b586658ed$export$199d6754bdf4e1e3} from "../utils/BreakpointProvider.js";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {filterDOMProps as $4SSrN$filterDOMProps} from "react-aria/filterDOMProps";
import $4SSrN$react, {forwardRef as $4SSrN$forwardRef} from "react";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
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






const $9b6884c982c0954a$var$flexStyleProps = {
    direction: [
        'flexDirection',
        (0, $120fbea2d95e11ed$export$46b6c81d11d2c30a)
    ],
    wrap: [
        'flexWrap',
        $9b6884c982c0954a$var$flexWrapValue
    ],
    justifyContent: [
        'justifyContent',
        $9b6884c982c0954a$var$flexAlignValue
    ],
    alignItems: [
        'alignItems',
        $9b6884c982c0954a$var$flexAlignValue
    ],
    alignContent: [
        'alignContent',
        $9b6884c982c0954a$var$flexAlignValue
    ]
};
const $9b6884c982c0954a$export$f51f4c4ede09e011 = /*#__PURE__*/ (0, $4SSrN$forwardRef)(function Flex(props, ref) {
    let { children: children, ...otherProps } = props;
    let breakpointProvider = (0, $cf1a1f4b586658ed$export$199d6754bdf4e1e3)();
    let matchedBreakpoints = (breakpointProvider === null || breakpointProvider === void 0 ? void 0 : breakpointProvider.matchedBreakpoints) || [
        'base'
    ];
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let { styleProps: flexStyle } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps, $9b6884c982c0954a$var$flexStyleProps);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let style = {
        ...styleProps.style,
        ...flexStyle.style
    };
    if (props.gap != null) style.gap = (0, $120fbea2d95e11ed$export$f348bec194f2e6b5)(props.gap, matchedBreakpoints);
    if (props.columnGap != null) style.columnGap = (0, $120fbea2d95e11ed$export$f348bec194f2e6b5)(props.columnGap, matchedBreakpoints);
    if (props.rowGap != null) style.rowGap = (0, $120fbea2d95e11ed$export$f348bec194f2e6b5)(props.rowGap, matchedBreakpoints);
    return /*#__PURE__*/ (0, $4SSrN$react).createElement("div", {
        ...(0, $4SSrN$filterDOMProps)(otherProps),
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4SSrN$flexgap_cssmjs))), 'flex', styleProps.className),
        style: style,
        ref: domRef
    }, children);
});
/**
 * Normalize 'start' and 'end' alignment values to 'flex-start' and 'flex-end'
 * in flex containers for browser compatibility.
 */ function $9b6884c982c0954a$var$flexAlignValue(value) {
    if (value === 'start') return 'flex-start';
    if (value === 'end') return 'flex-end';
    return value;
}
/**
 * Takes a boolean and translates it to flex wrap or nowrap.
 */ function $9b6884c982c0954a$var$flexWrapValue(value) {
    if (typeof value === 'boolean') return value ? 'wrap' : 'nowrap';
    return value;
}


export {$9b6884c982c0954a$export$f51f4c4ede09e011 as Flex};
//# sourceMappingURL=Flex.js.map
