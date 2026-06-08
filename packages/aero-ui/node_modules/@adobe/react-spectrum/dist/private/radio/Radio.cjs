var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../radio_vars.css");
var $bc827f1e79599754$exports = require("../radio_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $8ff76ba65921a904$exports = require("./context.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $8Lrwn$reactariauseRadioGroup = require("react-aria/useRadioGroup");
var $8Lrwn$reactariaFocusRing = require("react-aria/FocusRing");
var $8Lrwn$react = require("react");
var $8Lrwn$reactariauseHover = require("react-aria/useHover");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Radio", function () { return $520b58b40784e7fb$export$d7b12c4107be0d61; });
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








const $520b58b40784e7fb$export$d7b12c4107be0d61 = /*#__PURE__*/ (0, $8Lrwn$react.forwardRef)(function Radio(props, ref) {
    let { isDisabled: isDisabled, children: children, autoFocus: autoFocus, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $8Lrwn$reactariauseHover.useHover)({
        isDisabled: isDisabled
    });
    let inputRef = (0, $8Lrwn$react.useRef)(null);
    let domRef = (0, $65aea7b37663976b$exports.useFocusableRef)(ref, inputRef);
    let radioGroupProps = (0, $8ff76ba65921a904$exports.useRadioProvider)();
    let { isEmphasized: isEmphasized, state: state } = radioGroupProps;
    let { labelProps: labelProps, inputProps: inputProps } = (0, $8Lrwn$reactariauseRadioGroup.useRadio)({
        ...props,
        ...radioGroupProps,
        isDisabled: isDisabled
    }, state, inputRef);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8Lrwn$react))).createElement("label", {
        ...labelProps,
        ...styleProps,
        ...hoverProps,
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($bc827f1e79599754$exports))), 'spectrum-Radio', {
            // Removing. Pending design feedback.
            // 'spectrum-Radio--labelBelow': labelPosition === 'bottom',
            'spectrum-Radio--quiet': !isEmphasized,
            'is-disabled': isDisabled,
            'is-invalid': state.isInvalid,
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8Lrwn$react))).createElement((0, $8Lrwn$reactariaFocusRing.FocusRing), {
        focusRingClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($bc827f1e79599754$exports))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8Lrwn$react))).createElement("input", {
        ...inputProps,
        ref: inputRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($bc827f1e79599754$exports))), 'spectrum-Radio-input')
    })), /*#__PURE__*/ (0, ($parcel$interopDefault($8Lrwn$react))).createElement("span", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($bc827f1e79599754$exports))), 'spectrum-Radio-button')
    }), children && /*#__PURE__*/ (0, ($parcel$interopDefault($8Lrwn$react))).createElement("span", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($bc827f1e79599754$exports))), 'spectrum-Radio-label')
    }, children));
});


//# sourceMappingURL=Radio.cjs.map
