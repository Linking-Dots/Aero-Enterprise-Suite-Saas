import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../well_vars.css";
import $7q7ZX$well_vars_cssmjs from "../well_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {filterDOMProps as $7q7ZX$filterDOMProps} from "react-aria/filterDOMProps";
import $7q7ZX$react, {forwardRef as $7q7ZX$forwardRef} from "react";


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





const $a0f3ed92e1d5ff07$export$d89c679ee2f73242 = /*#__PURE__*/ (0, $7q7ZX$forwardRef)(function Well(props, ref) {
    let { children: children, role: role, ...otherProps } = props;
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    if (!role && (props['aria-label'] || props['aria-labelledby']) && process.env.NODE_ENV !== 'production') console.warn('A labelled Well must have a role.');
    return /*#__PURE__*/ (0, $7q7ZX$react).createElement("div", {
        ...(0, $7q7ZX$filterDOMProps)(otherProps, {
            labelable: !!role
        }),
        ...styleProps,
        role: role,
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($7q7ZX$well_vars_cssmjs))), 'spectrum-Well', styleProps.className)
    }, children);
});


export {$a0f3ed92e1d5ff07$export$d89c679ee2f73242 as Well};
//# sourceMappingURL=Well.js.map
