import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686} from "../utils/Slots.js";
import "../button_vars.css";
import $Svg0m$button_vars_cssmjs from "../button_vars_css.mjs";
import {Text as $42dd7396e689e4e6$export$5f1af8db9871e1d6} from "../text/Text.js";
import {useFocusableRef as $c234463e9ef56637$export$96a734597687c040} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {FocusRing as $Svg0m$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $Svg0m$mergeProps} from "react-aria/mergeProps";
import $Svg0m$react from "react";
import {useToggleButton as $Svg0m$useToggleButton} from "react-aria/useToggleButton";
import {useHover as $Svg0m$useHover} from "react-aria/useHover";
import {useToggleState as $Svg0m$useToggleState} from "react-stately/useToggleState";


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












const $e0864cdc7011a0bd$export$d2b052e7b4be1756 = /*#__PURE__*/ (0, $Svg0m$react).forwardRef(function ToggleButton(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { isQuiet: isQuiet, isDisabled: isDisabled, isEmphasized: isEmphasized, staticColor: staticColor, children: children, autoFocus: autoFocus, ...otherProps } = props;
    let domRef = (0, $c234463e9ef56637$export$96a734597687c040)(ref);
    let state = (0, $Svg0m$useToggleState)(props);
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $Svg0m$useToggleButton)(props, state, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $Svg0m$useHover)({
        isDisabled: isDisabled
    });
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let isTextOnly = (0, $Svg0m$react).Children.toArray(props.children).every((c)=>!/*#__PURE__*/ (0, $Svg0m$react).isValidElement(c));
    return /*#__PURE__*/ (0, $Svg0m$react).createElement((0, $Svg0m$FocusRing), {
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($Svg0m$button_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $Svg0m$react).createElement("button", {
        ...styleProps,
        ...(0, $Svg0m$mergeProps)(buttonProps, hoverProps),
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($Svg0m$button_vars_cssmjs))), 'spectrum-ActionButton', {
            'spectrum-ActionButton--quiet': isQuiet,
            'spectrum-ActionButton--emphasized': isEmphasized,
            'spectrum-ActionButton--staticColor': !!staticColor,
            'spectrum-ActionButton--staticWhite': staticColor === 'white',
            'spectrum-ActionButton--staticBlack': staticColor === 'black',
            'is-active': isPressed,
            'is-disabled': isDisabled,
            'is-hovered': isHovered,
            'is-selected': state.isSelected
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $Svg0m$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: {
            icon: {
                size: 'S',
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($Svg0m$button_vars_cssmjs))), 'spectrum-Icon')
            },
            text: {
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($Svg0m$button_vars_cssmjs))), 'spectrum-ActionButton-label')
            }
        }
    }, typeof children === 'string' || isTextOnly ? /*#__PURE__*/ (0, $Svg0m$react).createElement((0, $42dd7396e689e4e6$export$5f1af8db9871e1d6), null, children) : children)));
});


export {$e0864cdc7011a0bd$export$d2b052e7b4be1756 as ToggleButton};
//# sourceMappingURL=ToggleButton.js.map
