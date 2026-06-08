import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../button_vars.css";
import $f5g2M$button_vars_cssmjs from "../button_vars_css.mjs";
import {useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useButton as $f5g2M$useButton} from "react-aria/useButton";
import {FocusRing as $f5g2M$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $f5g2M$mergeProps} from "react-aria/mergeProps";
import $f5g2M$react from "react";
import {useHover as $f5g2M$useHover} from "react-aria/useHover";


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









const $11179b8f3194840b$export$9b0b80fed00ba8b1 = /*#__PURE__*/ (0, $f5g2M$react).forwardRef(function LogicButton(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { variant: variant, children: children, isDisabled: isDisabled, autoFocus: autoFocus, ...otherProps } = props;
    let domRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref);
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $f5g2M$useButton)(props, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $f5g2M$useHover)({
        isDisabled: isDisabled
    });
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    return /*#__PURE__*/ (0, $f5g2M$react).createElement((0, $f5g2M$FocusRing), {
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($f5g2M$button_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $f5g2M$react).createElement("button", {
        ...styleProps,
        ...(0, $f5g2M$mergeProps)(buttonProps, hoverProps),
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($f5g2M$button_vars_cssmjs))), 'spectrum-LogicButton', {
            [`spectrum-LogicButton--${variant}`]: variant,
            'is-disabled': isDisabled,
            'is-active': isPressed,
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $f5g2M$react).createElement("span", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($f5g2M$button_vars_cssmjs))), 'spectrum-Button-label')
    }, children)));
});


export {$11179b8f3194840b$export$9b0b80fed00ba8b1 as LogicButton};
//# sourceMappingURL=LogicButton.mjs.map
