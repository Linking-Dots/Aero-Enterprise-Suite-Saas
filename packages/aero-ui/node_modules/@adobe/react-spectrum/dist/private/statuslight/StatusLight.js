import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../statuslight_vars.css";
import $6Mu3X$statuslight_vars_cssmjs from "../statuslight_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {filterDOMProps as $6Mu3X$filterDOMProps} from "react-aria/filterDOMProps";
import $6Mu3X$react, {forwardRef as $6Mu3X$forwardRef} from "react";


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






const $2bbb23c6b3bb5597$export$5f84c37a31c6e41c = /*#__PURE__*/ (0, $6Mu3X$forwardRef)(function StatusLight(props, ref) {
    let { variant: variant, children: children, isDisabled: isDisabled, role: role, ...otherProps } = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    if (!children && !props['aria-label'] && process.env.NODE_ENV !== 'production') console.warn('If no children are provided, an aria-label must be specified');
    if (!role && (props['aria-label'] || props['aria-labelledby']) && process.env.NODE_ENV !== 'production') console.warn('A labelled StatusLight must have a role.');
    return /*#__PURE__*/ (0, $6Mu3X$react).createElement("div", {
        ...(0, $6Mu3X$filterDOMProps)(otherProps, {
            labelable: !!role
        }),
        ...styleProps,
        role: role,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($6Mu3X$statuslight_vars_cssmjs))), 'spectrum-StatusLight', `spectrum-StatusLight--${variant}`, {
            'is-disabled': isDisabled
        }, styleProps.className),
        ref: domRef
    }, children);
});


export {$2bbb23c6b3bb5597$export$5f84c37a31c6e41c as StatusLight};
//# sourceMappingURL=StatusLight.js.map
