import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../slider_vars.css";
import $lpEKF$slider_vars_cssmjs from "../slider_vars_css.mjs";
import {useSliderThumb as $lpEKF$useSliderThumb} from "react-aria/useSlider";
import {FocusRing as $lpEKF$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $lpEKF$mergeProps} from "react-aria/mergeProps";
import $lpEKF$react, {useRef as $lpEKF$useRef} from "react";
import {useHover as $lpEKF$useHover} from "react-aria/useHover";
import {VisuallyHidden as $lpEKF$VisuallyHidden} from "react-aria/VisuallyHidden";


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







function $adc3a7de1c061d22$export$2c1b491743890dec(props) {
    let { inputRef: inputRef, state: state } = props;
    let backupRef = (0, $lpEKF$useRef)(null);
    inputRef = inputRef || backupRef;
    let { thumbProps: thumbProps, inputProps: inputProps, isDragging: isDragging, isFocused: isFocused } = (0, $lpEKF$useSliderThumb)({
        ...props,
        inputRef: inputRef
    }, state);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $lpEKF$useHover)({});
    return /*#__PURE__*/ (0, $lpEKF$react).createElement((0, $lpEKF$FocusRing), {
        within: true,
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lpEKF$slider_vars_cssmjs))), 'is-focused')
    }, /*#__PURE__*/ (0, $lpEKF$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lpEKF$slider_vars_cssmjs))), 'spectrum-Slider-handle', {
            'is-hovered': isHovered,
            'is-dragged': isDragging,
            'is-tophandle': isFocused
        }),
        ...(0, $lpEKF$mergeProps)(thumbProps, hoverProps),
        role: "presentation"
    }, /*#__PURE__*/ (0, $lpEKF$react).createElement((0, $lpEKF$VisuallyHidden), null, /*#__PURE__*/ (0, $lpEKF$react).createElement("input", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lpEKF$slider_vars_cssmjs))), 'spectrum-Slider-input'),
        ref: inputRef,
        ...inputProps
    }))));
}


export {$adc3a7de1c061d22$export$2c1b491743890dec as SliderThumb};
//# sourceMappingURL=SliderThumb.js.map
