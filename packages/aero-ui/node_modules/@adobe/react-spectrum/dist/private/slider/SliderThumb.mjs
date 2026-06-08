import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../slider_vars.css";
import $gsRef$slider_vars_cssmjs from "../slider_vars_css.mjs";
import {useSliderThumb as $gsRef$useSliderThumb} from "react-aria/useSlider";
import {FocusRing as $gsRef$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $gsRef$mergeProps} from "react-aria/mergeProps";
import $gsRef$react, {useRef as $gsRef$useRef} from "react";
import {useHover as $gsRef$useHover} from "react-aria/useHover";
import {VisuallyHidden as $gsRef$VisuallyHidden} from "react-aria/VisuallyHidden";


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







function $fb57abd91cce4cfe$export$2c1b491743890dec(props) {
    let { inputRef: inputRef, state: state } = props;
    let backupRef = (0, $gsRef$useRef)(null);
    inputRef = inputRef || backupRef;
    let { thumbProps: thumbProps, inputProps: inputProps, isDragging: isDragging, isFocused: isFocused } = (0, $gsRef$useSliderThumb)({
        ...props,
        inputRef: inputRef
    }, state);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $gsRef$useHover)({});
    return /*#__PURE__*/ (0, $gsRef$react).createElement((0, $gsRef$FocusRing), {
        within: true,
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($gsRef$slider_vars_cssmjs))), 'is-focused')
    }, /*#__PURE__*/ (0, $gsRef$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($gsRef$slider_vars_cssmjs))), 'spectrum-Slider-handle', {
            'is-hovered': isHovered,
            'is-dragged': isDragging,
            'is-tophandle': isFocused
        }),
        ...(0, $gsRef$mergeProps)(thumbProps, hoverProps),
        role: "presentation"
    }, /*#__PURE__*/ (0, $gsRef$react).createElement((0, $gsRef$VisuallyHidden), null, /*#__PURE__*/ (0, $gsRef$react).createElement("input", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($gsRef$slider_vars_cssmjs))), 'spectrum-Slider-input'),
        ref: inputRef,
        ...inputProps
    }))));
}


export {$fb57abd91cce4cfe$export$2c1b491743890dec as SliderThumb};
//# sourceMappingURL=SliderThumb.mjs.map
