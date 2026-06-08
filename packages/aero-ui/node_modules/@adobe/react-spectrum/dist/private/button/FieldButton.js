import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686, useSlotProps as $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3} from "../utils/Slots.js";
import "../button_vars.css";
import $deCRp$button_vars_cssmjs from "../button_vars_css.mjs";
import {useFocusableRef as $c234463e9ef56637$export$96a734597687c040} from "../utils/useDOMRef.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useButton as $deCRp$useButton} from "react-aria/useButton";
import {FocusRing as $deCRp$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $deCRp$mergeProps} from "react-aria/mergeProps";
import $deCRp$react from "react";
import {useHover as $deCRp$useHover} from "react-aria/useHover";


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









const $1fa99bd0fd8b0a92$export$47dc48f595b075da = /*#__PURE__*/ (0, $deCRp$react).forwardRef(function FieldButton(props, ref) {
    props = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)(props, 'button');
    let { isQuiet: isQuiet, isDisabled: isDisabled, validationState: validationState, isInvalid: isInvalid, children: children, autoFocus: autoFocus, isActive: isActive, focusRingClass: focusRingClass, ...otherProps } = props;
    let domRef = (0, $c234463e9ef56637$export$96a734597687c040)(ref);
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $deCRp$useButton)(props, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $deCRp$useHover)({
        isDisabled: isDisabled
    });
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    return /*#__PURE__*/ (0, $deCRp$react).createElement((0, $deCRp$FocusRing), {
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($deCRp$button_vars_cssmjs))), 'focus-ring', focusRingClass),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $deCRp$react).createElement("button", {
        ...(0, $deCRp$mergeProps)(buttonProps, hoverProps),
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($deCRp$button_vars_cssmjs))), 'spectrum-FieldButton', {
            'spectrum-FieldButton--quiet': isQuiet,
            'is-active': isActive || isPressed,
            'is-disabled': isDisabled,
            'spectrum-FieldButton--invalid': isInvalid || validationState === 'invalid',
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $deCRp$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: {
            icon: {
                size: 'S',
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($deCRp$button_vars_cssmjs))), 'spectrum-Icon')
            }
        }
    }, children)));
});


export {$1fa99bd0fd8b0a92$export$47dc48f595b075da as FieldButton};
//# sourceMappingURL=FieldButton.js.map
