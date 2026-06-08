import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ClearSlots as $62024859ff9f1f8a$export$ceb145244332b7a2, SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686} from "../utils/Slots.mjs";
import "../badge_vars.css";
import $51TIT$badge_vars_cssmjs from "../badge_vars_css.mjs";
import {Text as $f8cc90fea9436c19$export$5f1af8db9871e1d6} from "../text/Text.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {filterDOMProps as $51TIT$filterDOMProps} from "react-aria/filterDOMProps";
import $51TIT$react, {forwardRef as $51TIT$forwardRef} from "react";


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








const $6a3af55e2c4e718c$export$37acb3580601e69a = /*#__PURE__*/ (0, $51TIT$forwardRef)(function Badge(props, ref) {
    let { children: children, variant: variant, ...otherProps } = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let isTextOnly = (0, $51TIT$react).Children.toArray(props.children).every((c)=>!/*#__PURE__*/ (0, $51TIT$react).isValidElement(c));
    return /*#__PURE__*/ (0, $51TIT$react).createElement("span", {
        ...(0, $51TIT$filterDOMProps)(otherProps),
        ...styleProps,
        role: "presentation",
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($51TIT$badge_vars_cssmjs))), 'spectrum-Badge', {
            [`spectrum-Badge--${variant}`]: variant
        }, styleProps.className),
        ref: domRef
    }, /*#__PURE__*/ (0, $51TIT$react).createElement((0, $62024859ff9f1f8a$export$ceb145244332b7a2), null, /*#__PURE__*/ (0, $51TIT$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: {
            icon: {
                size: 'S',
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($51TIT$badge_vars_cssmjs))), 'spectrum-Badge-icon')
            },
            text: {
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($51TIT$badge_vars_cssmjs))), 'spectrum-Badge-label')
            }
        }
    }, typeof children === 'string' || isTextOnly ? /*#__PURE__*/ (0, $51TIT$react).createElement((0, $f8cc90fea9436c19$export$5f1af8db9871e1d6), null, children) : children)));
});


export {$6a3af55e2c4e718c$export$37acb3580601e69a as Badge};
//# sourceMappingURL=Badge.mjs.map
