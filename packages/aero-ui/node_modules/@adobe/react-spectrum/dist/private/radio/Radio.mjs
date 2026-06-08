import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../radio_vars.css";
import $2IEVK$radio_vars_cssmjs from "../radio_vars_css.mjs";
import {useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {useRadioProvider as $561e3d9e91a7fb1c$export$b054eba74077a826} from "./context.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useRadio as $2IEVK$useRadio} from "react-aria/useRadioGroup";
import {FocusRing as $2IEVK$FocusRing} from "react-aria/FocusRing";
import $2IEVK$react, {forwardRef as $2IEVK$forwardRef, useRef as $2IEVK$useRef} from "react";
import {useHover as $2IEVK$useHover} from "react-aria/useHover";


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








const $590eb13bec0920f6$export$d7b12c4107be0d61 = /*#__PURE__*/ (0, $2IEVK$forwardRef)(function Radio(props, ref) {
    let { isDisabled: isDisabled, children: children, autoFocus: autoFocus, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $2IEVK$useHover)({
        isDisabled: isDisabled
    });
    let inputRef = (0, $2IEVK$useRef)(null);
    let domRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref, inputRef);
    let radioGroupProps = (0, $561e3d9e91a7fb1c$export$b054eba74077a826)();
    let { isEmphasized: isEmphasized, state: state } = radioGroupProps;
    let { labelProps: labelProps, inputProps: inputProps } = (0, $2IEVK$useRadio)({
        ...props,
        ...radioGroupProps,
        isDisabled: isDisabled
    }, state, inputRef);
    return /*#__PURE__*/ (0, $2IEVK$react).createElement("label", {
        ...labelProps,
        ...styleProps,
        ...hoverProps,
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2IEVK$radio_vars_cssmjs))), 'spectrum-Radio', {
            // Removing. Pending design feedback.
            // 'spectrum-Radio--labelBelow': labelPosition === 'bottom',
            'spectrum-Radio--quiet': !isEmphasized,
            'is-disabled': isDisabled,
            'is-invalid': state.isInvalid,
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $2IEVK$react).createElement((0, $2IEVK$FocusRing), {
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2IEVK$radio_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $2IEVK$react).createElement("input", {
        ...inputProps,
        ref: inputRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2IEVK$radio_vars_cssmjs))), 'spectrum-Radio-input')
    })), /*#__PURE__*/ (0, $2IEVK$react).createElement("span", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2IEVK$radio_vars_cssmjs))), 'spectrum-Radio-button')
    }), children && /*#__PURE__*/ (0, $2IEVK$react).createElement("span", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2IEVK$radio_vars_cssmjs))), 'spectrum-Radio-label')
    }, children));
});


export {$590eb13bec0920f6$export$d7b12c4107be0d61 as Radio};
//# sourceMappingURL=Radio.mjs.map
