var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../slider_vars.css");
var $2614471f25b42a54$exports = require("../slider_vars_css.cjs");
var $e6bJE$reactariauseSlider = require("react-aria/useSlider");
var $e6bJE$reactariaFocusRing = require("react-aria/FocusRing");
var $e6bJE$reactariamergeProps = require("react-aria/mergeProps");
var $e6bJE$react = require("react");
var $e6bJE$reactariauseHover = require("react-aria/useHover");
var $e6bJE$reactariaVisuallyHidden = require("react-aria/VisuallyHidden");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "SliderThumb", function () { return $492d3e2308ba15ca$export$2c1b491743890dec; });
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







function $492d3e2308ba15ca$export$2c1b491743890dec(props) {
    let { inputRef: inputRef, state: state } = props;
    let backupRef = (0, $e6bJE$react.useRef)(null);
    inputRef = inputRef || backupRef;
    let { thumbProps: thumbProps, inputProps: inputProps, isDragging: isDragging, isFocused: isFocused } = (0, $e6bJE$reactariauseSlider.useSliderThumb)({
        ...props,
        inputRef: inputRef
    }, state);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $e6bJE$reactariauseHover.useHover)({});
    return /*#__PURE__*/ (0, ($parcel$interopDefault($e6bJE$react))).createElement((0, $e6bJE$reactariaFocusRing.FocusRing), {
        within: true,
        focusRingClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($2614471f25b42a54$exports))), 'is-focused')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($e6bJE$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($2614471f25b42a54$exports))), 'spectrum-Slider-handle', {
            'is-hovered': isHovered,
            'is-dragged': isDragging,
            'is-tophandle': isFocused
        }),
        ...(0, $e6bJE$reactariamergeProps.mergeProps)(thumbProps, hoverProps),
        role: "presentation"
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($e6bJE$react))).createElement((0, $e6bJE$reactariaVisuallyHidden.VisuallyHidden), null, /*#__PURE__*/ (0, ($parcel$interopDefault($e6bJE$react))).createElement("input", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($2614471f25b42a54$exports))), 'spectrum-Slider-input'),
        ref: inputRef,
        ...inputProps
    }))));
}


//# sourceMappingURL=SliderThumb.cjs.map
