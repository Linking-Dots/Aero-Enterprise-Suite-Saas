import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../radio_vars.css";
import $9zzq9$radio_vars_cssmjs from "../radio_vars_css.mjs";
import {useFocusableRef as $c234463e9ef56637$export$96a734597687c040} from "../utils/useDOMRef.js";
import {useRadioProvider as $d94927a7c7b6e45d$export$b054eba74077a826} from "./context.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useRadio as $9zzq9$useRadio} from "react-aria/useRadioGroup";
import {FocusRing as $9zzq9$FocusRing} from "react-aria/FocusRing";
import $9zzq9$react, {forwardRef as $9zzq9$forwardRef, useRef as $9zzq9$useRef} from "react";
import {useHover as $9zzq9$useHover} from "react-aria/useHover";


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








const $27a2aa405605847c$export$d7b12c4107be0d61 = /*#__PURE__*/ (0, $9zzq9$forwardRef)(function Radio(props, ref) {
    let { isDisabled: isDisabled, children: children, autoFocus: autoFocus, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $9zzq9$useHover)({
        isDisabled: isDisabled
    });
    let inputRef = (0, $9zzq9$useRef)(null);
    let domRef = (0, $c234463e9ef56637$export$96a734597687c040)(ref, inputRef);
    let radioGroupProps = (0, $d94927a7c7b6e45d$export$b054eba74077a826)();
    let { isEmphasized: isEmphasized, state: state } = radioGroupProps;
    let { labelProps: labelProps, inputProps: inputProps } = (0, $9zzq9$useRadio)({
        ...props,
        ...radioGroupProps,
        isDisabled: isDisabled
    }, state, inputRef);
    return /*#__PURE__*/ (0, $9zzq9$react).createElement("label", {
        ...labelProps,
        ...styleProps,
        ...hoverProps,
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9zzq9$radio_vars_cssmjs))), 'spectrum-Radio', {
            // Removing. Pending design feedback.
            // 'spectrum-Radio--labelBelow': labelPosition === 'bottom',
            'spectrum-Radio--quiet': !isEmphasized,
            'is-disabled': isDisabled,
            'is-invalid': state.isInvalid,
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $9zzq9$react).createElement((0, $9zzq9$FocusRing), {
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9zzq9$radio_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $9zzq9$react).createElement("input", {
        ...inputProps,
        ref: inputRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9zzq9$radio_vars_cssmjs))), 'spectrum-Radio-input')
    })), /*#__PURE__*/ (0, $9zzq9$react).createElement("span", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9zzq9$radio_vars_cssmjs))), 'spectrum-Radio-button')
    }), children && /*#__PURE__*/ (0, $9zzq9$react).createElement("span", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9zzq9$radio_vars_cssmjs))), 'spectrum-Radio-label')
    }, children));
});


export {$27a2aa405605847c$export$d7b12c4107be0d61 as Radio};
//# sourceMappingURL=Radio.js.map
