import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686, useSlotProps as $62024859ff9f1f8a$export$1e5c9e6e4e15efe3} from "../utils/Slots.mjs";
import "../button_vars.css";
import $k9F5V$button_vars_cssmjs from "../button_vars_css.mjs";
import {useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useButton as $k9F5V$useButton} from "react-aria/useButton";
import {FocusRing as $k9F5V$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $k9F5V$mergeProps} from "react-aria/mergeProps";
import $k9F5V$react from "react";
import {useHover as $k9F5V$useHover} from "react-aria/useHover";


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









const $9b445aa2bd8cce4c$export$47dc48f595b075da = /*#__PURE__*/ (0, $k9F5V$react).forwardRef(function FieldButton(props, ref) {
    props = (0, $62024859ff9f1f8a$export$1e5c9e6e4e15efe3)(props, 'button');
    let { isQuiet: isQuiet, isDisabled: isDisabled, validationState: validationState, isInvalid: isInvalid, children: children, autoFocus: autoFocus, isActive: isActive, focusRingClass: focusRingClass, ...otherProps } = props;
    let domRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref);
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $k9F5V$useButton)(props, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $k9F5V$useHover)({
        isDisabled: isDisabled
    });
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    return /*#__PURE__*/ (0, $k9F5V$react).createElement((0, $k9F5V$FocusRing), {
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($k9F5V$button_vars_cssmjs))), 'focus-ring', focusRingClass),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $k9F5V$react).createElement("button", {
        ...(0, $k9F5V$mergeProps)(buttonProps, hoverProps),
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($k9F5V$button_vars_cssmjs))), 'spectrum-FieldButton', {
            'spectrum-FieldButton--quiet': isQuiet,
            'is-active': isActive || isPressed,
            'is-disabled': isDisabled,
            'spectrum-FieldButton--invalid': isInvalid || validationState === 'invalid',
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $k9F5V$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: {
            icon: {
                size: 'S',
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($k9F5V$button_vars_cssmjs))), 'spectrum-Icon')
            }
        }
    }, children)));
});


export {$9b445aa2bd8cce4c$export$47dc48f595b075da as FieldButton};
//# sourceMappingURL=FieldButton.mjs.map
