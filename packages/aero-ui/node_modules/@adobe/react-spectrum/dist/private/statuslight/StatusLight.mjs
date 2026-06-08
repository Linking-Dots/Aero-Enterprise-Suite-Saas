import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../statuslight_vars.css";
import $hEMdz$statuslight_vars_cssmjs from "../statuslight_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {filterDOMProps as $hEMdz$filterDOMProps} from "react-aria/filterDOMProps";
import $hEMdz$react, {forwardRef as $hEMdz$forwardRef} from "react";


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






const $020886eee8d168b7$export$5f84c37a31c6e41c = /*#__PURE__*/ (0, $hEMdz$forwardRef)(function StatusLight(props, ref) {
    let { variant: variant, children: children, isDisabled: isDisabled, role: role, ...otherProps } = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    if (!children && !props['aria-label'] && process.env.NODE_ENV !== 'production') console.warn('If no children are provided, an aria-label must be specified');
    if (!role && (props['aria-label'] || props['aria-labelledby']) && process.env.NODE_ENV !== 'production') console.warn('A labelled StatusLight must have a role.');
    return /*#__PURE__*/ (0, $hEMdz$react).createElement("div", {
        ...(0, $hEMdz$filterDOMProps)(otherProps, {
            labelable: !!role
        }),
        ...styleProps,
        role: role,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hEMdz$statuslight_vars_cssmjs))), 'spectrum-StatusLight', `spectrum-StatusLight--${variant}`, {
            'is-disabled': isDisabled
        }, styleProps.className),
        ref: domRef
    }, children);
});


export {$020886eee8d168b7$export$5f84c37a31c6e41c as StatusLight};
//# sourceMappingURL=StatusLight.mjs.map
