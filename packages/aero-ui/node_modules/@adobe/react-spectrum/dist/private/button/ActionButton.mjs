import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ClearSlots as $62024859ff9f1f8a$export$ceb145244332b7a2, SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686, useSlotProps as $62024859ff9f1f8a$export$1e5c9e6e4e15efe3} from "../utils/Slots.mjs";
import "../button_vars.css";
import $jbuei$button_vars_cssmjs from "../button_vars_css.mjs";
import {Text as $f8cc90fea9436c19$export$5f1af8db9871e1d6} from "../text/Text.mjs";
import {useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useButton as $jbuei$useButton} from "react-aria/useButton";
import $jbuei$spectrumiconsuiCornerTriangle from "@spectrum-icons/ui/CornerTriangle";
import {FocusRing as $jbuei$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $jbuei$mergeProps} from "react-aria/mergeProps";
import $jbuei$react from "react";
import {useHover as $jbuei$useHover} from "react-aria/useHover";


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












const $b41412308e87d8d9$export$cfc7921d29ef7b80 = /*#__PURE__*/ (0, $jbuei$react).forwardRef(function ActionButton(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    props = (0, $62024859ff9f1f8a$export$1e5c9e6e4e15efe3)(props, 'actionButton');
    let textProps = (0, $62024859ff9f1f8a$export$1e5c9e6e4e15efe3)({
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($jbuei$button_vars_cssmjs))), 'spectrum-ActionButton-label')
    }, 'text');
    let { isQuiet: isQuiet, isDisabled: isDisabled, staticColor: staticColor, children: children, autoFocus: autoFocus, holdAffordance: // @ts-ignore (private)
    holdAffordance, hideButtonText: // @ts-ignore (private)
    hideButtonText, ...otherProps } = props;
    let domRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref);
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $jbuei$useButton)(props, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $jbuei$useHover)({
        isDisabled: isDisabled
    });
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let isTextOnly = (0, $jbuei$react).Children.toArray(props.children).every((c)=>!/*#__PURE__*/ (0, $jbuei$react).isValidElement(c));
    return /*#__PURE__*/ (0, $jbuei$react).createElement((0, $jbuei$FocusRing), {
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($jbuei$button_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $jbuei$react).createElement("button", {
        ...styleProps,
        ...(0, $jbuei$mergeProps)(buttonProps, hoverProps),
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($jbuei$button_vars_cssmjs))), 'spectrum-ActionButton', {
            'spectrum-ActionButton--quiet': isQuiet,
            'spectrum-ActionButton--staticColor': !!staticColor,
            'spectrum-ActionButton--staticWhite': staticColor === 'white',
            'spectrum-ActionButton--staticBlack': staticColor === 'black',
            'is-active': isPressed,
            'is-disabled': isDisabled,
            'is-hovered': isHovered
        }, styleProps.className)
    }, holdAffordance && /*#__PURE__*/ (0, $jbuei$react).createElement((0, $jbuei$spectrumiconsuiCornerTriangle), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($jbuei$button_vars_cssmjs))), 'spectrum-ActionButton-hold')
    }), /*#__PURE__*/ (0, $jbuei$react).createElement((0, $62024859ff9f1f8a$export$ceb145244332b7a2), null, /*#__PURE__*/ (0, $jbuei$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: {
            icon: {
                size: 'S',
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($jbuei$button_vars_cssmjs))), 'spectrum-Icon', {
                    'spectrum-ActionGroup-itemIcon': hideButtonText
                })
            },
            text: {
                ...textProps
            }
        }
    }, typeof children === 'string' || isTextOnly ? /*#__PURE__*/ (0, $jbuei$react).createElement((0, $f8cc90fea9436c19$export$5f1af8db9871e1d6), null, children) : children))));
});


export {$b41412308e87d8d9$export$cfc7921d29ef7b80 as ActionButton};
//# sourceMappingURL=ActionButton.mjs.map
