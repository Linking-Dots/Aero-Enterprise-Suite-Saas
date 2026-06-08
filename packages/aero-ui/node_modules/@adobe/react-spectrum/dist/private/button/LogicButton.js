import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../button_vars.css";
import $lwxsv$button_vars_cssmjs from "../button_vars_css.mjs";
import {useFocusableRef as $c234463e9ef56637$export$96a734597687c040} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useButton as $lwxsv$useButton} from "react-aria/useButton";
import {FocusRing as $lwxsv$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $lwxsv$mergeProps} from "react-aria/mergeProps";
import $lwxsv$react from "react";
import {useHover as $lwxsv$useHover} from "react-aria/useHover";


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









const $9bb0441a39d7224a$export$9b0b80fed00ba8b1 = /*#__PURE__*/ (0, $lwxsv$react).forwardRef(function LogicButton(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { variant: variant, children: children, isDisabled: isDisabled, autoFocus: autoFocus, ...otherProps } = props;
    let domRef = (0, $c234463e9ef56637$export$96a734597687c040)(ref);
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $lwxsv$useButton)(props, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $lwxsv$useHover)({
        isDisabled: isDisabled
    });
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    return /*#__PURE__*/ (0, $lwxsv$react).createElement((0, $lwxsv$FocusRing), {
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lwxsv$button_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $lwxsv$react).createElement("button", {
        ...styleProps,
        ...(0, $lwxsv$mergeProps)(buttonProps, hoverProps),
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lwxsv$button_vars_cssmjs))), 'spectrum-LogicButton', {
            [`spectrum-LogicButton--${variant}`]: variant,
            'is-disabled': isDisabled,
            'is-active': isPressed,
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $lwxsv$react).createElement("span", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lwxsv$button_vars_cssmjs))), 'spectrum-Button-label')
    }, children)));
});


export {$9bb0441a39d7224a$export$9b0b80fed00ba8b1 as LogicButton};
//# sourceMappingURL=LogicButton.js.map
