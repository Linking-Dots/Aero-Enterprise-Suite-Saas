import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {ClearSlots as $68f4bc2c1abc5618$export$ceb145244332b7a2, SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686} from "../utils/Slots.js";
import "../badge_vars.css";
import $eYioC$badge_vars_cssmjs from "../badge_vars_css.mjs";
import {Text as $42dd7396e689e4e6$export$5f1af8db9871e1d6} from "../text/Text.js";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {filterDOMProps as $eYioC$filterDOMProps} from "react-aria/filterDOMProps";
import $eYioC$react, {forwardRef as $eYioC$forwardRef} from "react";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
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








const $b1b5eaed19db7dfb$export$37acb3580601e69a = /*#__PURE__*/ (0, $eYioC$forwardRef)(function Badge(props, ref) {
    let { children: children, variant: variant, ...otherProps } = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let isTextOnly = (0, $eYioC$react).Children.toArray(props.children).every((c)=>!/*#__PURE__*/ (0, $eYioC$react).isValidElement(c));
    return /*#__PURE__*/ (0, $eYioC$react).createElement("span", {
        ...(0, $eYioC$filterDOMProps)(otherProps),
        ...styleProps,
        role: "presentation",
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eYioC$badge_vars_cssmjs))), 'spectrum-Badge', {
            [`spectrum-Badge--${variant}`]: variant
        }, styleProps.className),
        ref: domRef
    }, /*#__PURE__*/ (0, $eYioC$react).createElement((0, $68f4bc2c1abc5618$export$ceb145244332b7a2), null, /*#__PURE__*/ (0, $eYioC$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: {
            icon: {
                size: 'S',
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eYioC$badge_vars_cssmjs))), 'spectrum-Badge-icon')
            },
            text: {
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eYioC$badge_vars_cssmjs))), 'spectrum-Badge-label')
            }
        }
    }, typeof children === 'string' || isTextOnly ? /*#__PURE__*/ (0, $eYioC$react).createElement((0, $42dd7396e689e4e6$export$5f1af8db9871e1d6), null, children) : children)));
});


export {$b1b5eaed19db7dfb$export$37acb3580601e69a as Badge};
//# sourceMappingURL=Badge.js.map
