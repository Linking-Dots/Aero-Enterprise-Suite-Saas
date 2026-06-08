import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../well_vars.css";
import $ceS37$well_vars_cssmjs from "../well_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {filterDOMProps as $ceS37$filterDOMProps} from "react-aria/filterDOMProps";
import $ceS37$react, {forwardRef as $ceS37$forwardRef} from "react";


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





const $d8a90d7dc54542bb$export$d89c679ee2f73242 = /*#__PURE__*/ (0, $ceS37$forwardRef)(function Well(props, ref) {
    let { children: children, role: role, ...otherProps } = props;
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    if (!role && (props['aria-label'] || props['aria-labelledby']) && process.env.NODE_ENV !== 'production') console.warn('A labelled Well must have a role.');
    return /*#__PURE__*/ (0, $ceS37$react).createElement("div", {
        ...(0, $ceS37$filterDOMProps)(otherProps, {
            labelable: !!role
        }),
        ...styleProps,
        role: role,
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ceS37$well_vars_cssmjs))), 'spectrum-Well', styleProps.className)
    }, children);
});


export {$d8a90d7dc54542bb$export$d89c679ee2f73242 as Well};
//# sourceMappingURL=Well.mjs.map
