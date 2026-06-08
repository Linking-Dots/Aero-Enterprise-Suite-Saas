var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../button_vars.css");
var $869138cbe3b599dc$exports = require("../button_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $cMFOu$reactariauseButton = require("react-aria/useButton");
var $cMFOu$reactariaFocusRing = require("react-aria/FocusRing");
var $cMFOu$reactariamergeProps = require("react-aria/mergeProps");
var $cMFOu$react = require("react");
var $cMFOu$reactariauseHover = require("react-aria/useHover");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "LogicButton", function () { return $74591a8f5f98b397$export$9b0b80fed00ba8b1; });
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









const $74591a8f5f98b397$export$9b0b80fed00ba8b1 = /*#__PURE__*/ (0, ($parcel$interopDefault($cMFOu$react))).forwardRef(function LogicButton(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { variant: variant, children: children, isDisabled: isDisabled, autoFocus: autoFocus, ...otherProps } = props;
    let domRef = (0, $65aea7b37663976b$exports.useFocusableRef)(ref);
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $cMFOu$reactariauseButton.useButton)(props, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $cMFOu$reactariauseHover.useHover)({
        isDisabled: isDisabled
    });
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($cMFOu$react))).createElement((0, $cMFOu$reactariaFocusRing.FocusRing), {
        focusRingClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($869138cbe3b599dc$exports))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($cMFOu$react))).createElement("button", {
        ...styleProps,
        ...(0, $cMFOu$reactariamergeProps.mergeProps)(buttonProps, hoverProps),
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($869138cbe3b599dc$exports))), 'spectrum-LogicButton', {
            [`spectrum-LogicButton--${variant}`]: variant,
            'is-disabled': isDisabled,
            'is-active': isPressed,
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($cMFOu$react))).createElement("span", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($869138cbe3b599dc$exports))), 'spectrum-Button-label')
    }, children)));
});


//# sourceMappingURL=LogicButton.cjs.map
