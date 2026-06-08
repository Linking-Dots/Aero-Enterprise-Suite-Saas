import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../toggle_vars.css";
import $1y4s1$toggle_vars_cssmjs from "../toggle_vars_css.mjs";
import {useFocusableRef as $c234463e9ef56637$export$96a734597687c040} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useSwitch as $1y4s1$useSwitch} from "react-aria/useSwitch";
import {FocusRing as $1y4s1$FocusRing} from "react-aria/FocusRing";
import $1y4s1$react, {forwardRef as $1y4s1$forwardRef, useRef as $1y4s1$useRef} from "react";
import {useHover as $1y4s1$useHover} from "react-aria/useHover";
import {useToggleState as $1y4s1$useToggleState} from "react-stately/useToggleState";


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









const $0d6506a6fb520294$export$b5d5cf8927ab7262 = /*#__PURE__*/ (0, $1y4s1$forwardRef)(function Switch(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { isEmphasized: isEmphasized = false, isDisabled: isDisabled = false, autoFocus: autoFocus, children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $1y4s1$useHover)({
        isDisabled: isDisabled
    });
    let inputRef = (0, $1y4s1$useRef)(null);
    let domRef = (0, $c234463e9ef56637$export$96a734597687c040)(ref, inputRef);
    let state = (0, $1y4s1$useToggleState)(props);
    let { labelProps: labelProps, inputProps: inputProps } = (0, $1y4s1$useSwitch)(props, state, inputRef);
    return /*#__PURE__*/ (0, $1y4s1$react).createElement("label", {
        ...labelProps,
        ...styleProps,
        ...hoverProps,
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1y4s1$toggle_vars_cssmjs))), 'spectrum-ToggleSwitch', {
            'spectrum-ToggleSwitch--quiet': !isEmphasized,
            'is-disabled': isDisabled,
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $1y4s1$react).createElement((0, $1y4s1$FocusRing), {
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1y4s1$toggle_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $1y4s1$react).createElement("input", {
        ...inputProps,
        ref: inputRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1y4s1$toggle_vars_cssmjs))), 'spectrum-ToggleSwitch-input')
    })), /*#__PURE__*/ (0, $1y4s1$react).createElement("span", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1y4s1$toggle_vars_cssmjs))), 'spectrum-ToggleSwitch-switch')
    }), children && /*#__PURE__*/ (0, $1y4s1$react).createElement("span", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1y4s1$toggle_vars_cssmjs))), 'spectrum-ToggleSwitch-label')
    }, children));
});


export {$0d6506a6fb520294$export$b5d5cf8927ab7262 as Switch};
//# sourceMappingURL=Switch.js.map
