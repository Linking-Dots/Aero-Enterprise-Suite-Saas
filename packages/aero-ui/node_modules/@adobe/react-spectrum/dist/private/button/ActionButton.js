import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {ClearSlots as $68f4bc2c1abc5618$export$ceb145244332b7a2, SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686, useSlotProps as $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3} from "../utils/Slots.js";
import "../button_vars.css";
import $2bzzV$button_vars_cssmjs from "../button_vars_css.mjs";
import {Text as $42dd7396e689e4e6$export$5f1af8db9871e1d6} from "../text/Text.js";
import {useFocusableRef as $c234463e9ef56637$export$96a734597687c040} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useButton as $2bzzV$useButton} from "react-aria/useButton";
import $2bzzV$spectrumiconsuiCornerTriangle from "@spectrum-icons/ui/CornerTriangle";
import {FocusRing as $2bzzV$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $2bzzV$mergeProps} from "react-aria/mergeProps";
import $2bzzV$react from "react";
import {useHover as $2bzzV$useHover} from "react-aria/useHover";


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












const $c265dbb41bfd0210$export$cfc7921d29ef7b80 = /*#__PURE__*/ (0, $2bzzV$react).forwardRef(function ActionButton(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    props = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)(props, 'actionButton');
    let textProps = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)({
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2bzzV$button_vars_cssmjs))), 'spectrum-ActionButton-label')
    }, 'text');
    let { isQuiet: isQuiet, isDisabled: isDisabled, staticColor: staticColor, children: children, autoFocus: autoFocus, holdAffordance: // @ts-ignore (private)
    holdAffordance, hideButtonText: // @ts-ignore (private)
    hideButtonText, ...otherProps } = props;
    let domRef = (0, $c234463e9ef56637$export$96a734597687c040)(ref);
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $2bzzV$useButton)(props, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $2bzzV$useHover)({
        isDisabled: isDisabled
    });
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let isTextOnly = (0, $2bzzV$react).Children.toArray(props.children).every((c)=>!/*#__PURE__*/ (0, $2bzzV$react).isValidElement(c));
    return /*#__PURE__*/ (0, $2bzzV$react).createElement((0, $2bzzV$FocusRing), {
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2bzzV$button_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $2bzzV$react).createElement("button", {
        ...styleProps,
        ...(0, $2bzzV$mergeProps)(buttonProps, hoverProps),
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2bzzV$button_vars_cssmjs))), 'spectrum-ActionButton', {
            'spectrum-ActionButton--quiet': isQuiet,
            'spectrum-ActionButton--staticColor': !!staticColor,
            'spectrum-ActionButton--staticWhite': staticColor === 'white',
            'spectrum-ActionButton--staticBlack': staticColor === 'black',
            'is-active': isPressed,
            'is-disabled': isDisabled,
            'is-hovered': isHovered
        }, styleProps.className)
    }, holdAffordance && /*#__PURE__*/ (0, $2bzzV$react).createElement((0, $2bzzV$spectrumiconsuiCornerTriangle), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2bzzV$button_vars_cssmjs))), 'spectrum-ActionButton-hold')
    }), /*#__PURE__*/ (0, $2bzzV$react).createElement((0, $68f4bc2c1abc5618$export$ceb145244332b7a2), null, /*#__PURE__*/ (0, $2bzzV$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: {
            icon: {
                size: 'S',
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2bzzV$button_vars_cssmjs))), 'spectrum-Icon', {
                    'spectrum-ActionGroup-itemIcon': hideButtonText
                })
            },
            text: {
                ...textProps
            }
        }
    }, typeof children === 'string' || isTextOnly ? /*#__PURE__*/ (0, $2bzzV$react).createElement((0, $42dd7396e689e4e6$export$5f1af8db9871e1d6), null, children) : children))));
});


export {$c265dbb41bfd0210$export$cfc7921d29ef7b80 as ActionButton};
//# sourceMappingURL=ActionButton.js.map
