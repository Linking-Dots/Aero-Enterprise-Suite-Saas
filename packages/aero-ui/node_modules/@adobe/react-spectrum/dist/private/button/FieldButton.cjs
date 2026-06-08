var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
require("../button_vars.css");
var $869138cbe3b599dc$exports = require("../button_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $ec68N$reactariauseButton = require("react-aria/useButton");
var $ec68N$reactariaFocusRing = require("react-aria/FocusRing");
var $ec68N$reactariamergeProps = require("react-aria/mergeProps");
var $ec68N$react = require("react");
var $ec68N$reactariauseHover = require("react-aria/useHover");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "FieldButton", function () { return $23798a2a76e33abb$export$47dc48f595b075da; });
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









const $23798a2a76e33abb$export$47dc48f595b075da = /*#__PURE__*/ (0, ($parcel$interopDefault($ec68N$react))).forwardRef(function FieldButton(props, ref) {
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'button');
    let { isQuiet: isQuiet, isDisabled: isDisabled, validationState: validationState, isInvalid: isInvalid, children: children, autoFocus: autoFocus, isActive: isActive, focusRingClass: focusRingClass, ...otherProps } = props;
    let domRef = (0, $65aea7b37663976b$exports.useFocusableRef)(ref);
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $ec68N$reactariauseButton.useButton)(props, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $ec68N$reactariauseHover.useHover)({
        isDisabled: isDisabled
    });
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($ec68N$react))).createElement((0, $ec68N$reactariaFocusRing.FocusRing), {
        focusRingClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($869138cbe3b599dc$exports))), 'focus-ring', focusRingClass),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($ec68N$react))).createElement("button", {
        ...(0, $ec68N$reactariamergeProps.mergeProps)(buttonProps, hoverProps),
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($869138cbe3b599dc$exports))), 'spectrum-FieldButton', {
            'spectrum-FieldButton--quiet': isQuiet,
            'is-active': isActive || isPressed,
            'is-disabled': isDisabled,
            'spectrum-FieldButton--invalid': isInvalid || validationState === 'invalid',
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($ec68N$react))).createElement((0, $feede71cddc0c5f3$exports.SlotProvider), {
        slots: {
            icon: {
                size: 'S',
                UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($869138cbe3b599dc$exports))), 'spectrum-Icon')
            }
        }
    }, children)));
});


//# sourceMappingURL=FieldButton.cjs.map
