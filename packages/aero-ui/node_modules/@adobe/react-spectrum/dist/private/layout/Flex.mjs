import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {passthroughStyle as $63d03c54ca5e4b88$export$46b6c81d11d2c30a, responsiveDimensionValue as $63d03c54ca5e4b88$export$f348bec194f2e6b5, useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import "./flex-gap.css";
import $3pii1$flexgap_cssmjs from "./flex-gap_css.mjs";
import {useBreakpoint as $367536236d783ddf$export$199d6754bdf4e1e3} from "../utils/BreakpointProvider.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {filterDOMProps as $3pii1$filterDOMProps} from "react-aria/filterDOMProps";
import $3pii1$react, {forwardRef as $3pii1$forwardRef} from "react";


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






const $ec3baf921918e057$var$flexStyleProps = {
    direction: [
        'flexDirection',
        (0, $63d03c54ca5e4b88$export$46b6c81d11d2c30a)
    ],
    wrap: [
        'flexWrap',
        $ec3baf921918e057$var$flexWrapValue
    ],
    justifyContent: [
        'justifyContent',
        $ec3baf921918e057$var$flexAlignValue
    ],
    alignItems: [
        'alignItems',
        $ec3baf921918e057$var$flexAlignValue
    ],
    alignContent: [
        'alignContent',
        $ec3baf921918e057$var$flexAlignValue
    ]
};
const $ec3baf921918e057$export$f51f4c4ede09e011 = /*#__PURE__*/ (0, $3pii1$forwardRef)(function Flex(props, ref) {
    let { children: children, ...otherProps } = props;
    let breakpointProvider = (0, $367536236d783ddf$export$199d6754bdf4e1e3)();
    let matchedBreakpoints = breakpointProvider?.matchedBreakpoints || [
        'base'
    ];
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let { styleProps: flexStyle } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps, $ec3baf921918e057$var$flexStyleProps);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let style = {
        ...styleProps.style,
        ...flexStyle.style
    };
    if (props.gap != null) style.gap = (0, $63d03c54ca5e4b88$export$f348bec194f2e6b5)(props.gap, matchedBreakpoints);
    if (props.columnGap != null) style.columnGap = (0, $63d03c54ca5e4b88$export$f348bec194f2e6b5)(props.columnGap, matchedBreakpoints);
    if (props.rowGap != null) style.rowGap = (0, $63d03c54ca5e4b88$export$f348bec194f2e6b5)(props.rowGap, matchedBreakpoints);
    return /*#__PURE__*/ (0, $3pii1$react).createElement("div", {
        ...(0, $3pii1$filterDOMProps)(otherProps),
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3pii1$flexgap_cssmjs))), 'flex', styleProps.className),
        style: style,
        ref: domRef
    }, children);
});
/**
 * Normalize 'start' and 'end' alignment values to 'flex-start' and 'flex-end'
 * in flex containers for browser compatibility.
 */ function $ec3baf921918e057$var$flexAlignValue(value) {
    if (value === 'start') return 'flex-start';
    if (value === 'end') return 'flex-end';
    return value;
}
/**
 * Takes a boolean and translates it to flex wrap or nowrap.
 */ function $ec3baf921918e057$var$flexWrapValue(value) {
    if (typeof value === 'boolean') return value ? 'wrap' : 'nowrap';
    return value;
}


export {$ec3baf921918e057$export$f51f4c4ede09e011 as Flex};
//# sourceMappingURL=Flex.mjs.map
