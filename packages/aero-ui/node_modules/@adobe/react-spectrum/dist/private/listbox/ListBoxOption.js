import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {ClearSlots as $68f4bc2c1abc5618$export$ceb145244332b7a2, SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686} from "../utils/Slots.js";
import {Grid as $727c1a1d9e8b8d73$export$ef2184bd89960b14} from "../layout/Grid.js";
import {ListBoxContext as $90de0e4b1949420b$export$7ff8f37d2d81a48d} from "./ListBoxContext.js";
import "../menu_vars.css";
import $cZGjp$menu_vars_cssmjs from "../menu_vars_css.mjs";
import {Text as $42dd7396e689e4e6$export$5f1af8db9871e1d6} from "../text/Text.js";
import $cZGjp$spectrumiconsuiCheckmarkMedium from "@spectrum-icons/ui/CheckmarkMedium";
import {FocusRing as $cZGjp$FocusRing} from "react-aria/FocusRing";
import {isFocusVisible as $cZGjp$isFocusVisible} from "react-aria/private/interactions/useFocusVisible";
import {mergeProps as $cZGjp$mergeProps} from "react-aria/mergeProps";
import $cZGjp$react, {useContext as $cZGjp$useContext, useRef as $cZGjp$useRef} from "react";
import {useHover as $cZGjp$useHover} from "react-aria/useHover";
import {useOption as $cZGjp$useOption} from "react-aria/useListBox";


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












function $1ff51a8d0dceabe5$export$feb3b6b552c14a12(props) {
    let { item: item } = props;
    let { rendered: rendered, key: key } = item;
    let ElementType = item.props.href ? 'a' : 'div';
    let { state: state, shouldFocusOnHover: shouldFocusOnHover, shouldUseVirtualFocus: shouldUseVirtualFocus } = (0, $cZGjp$useContext)((0, $90de0e4b1949420b$export$7ff8f37d2d81a48d));
    let ref = (0, $cZGjp$useRef)(undefined);
    let { optionProps: optionProps, labelProps: labelProps, descriptionProps: descriptionProps, isSelected: isSelected, isDisabled: isDisabled, isFocused: isFocused } = (0, $cZGjp$useOption)({
        'aria-label': item['aria-label'],
        key: key,
        isVirtualized: true
    }, state, ref);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $cZGjp$useHover)({
        ...props,
        isDisabled: isDisabled
    });
    let contents = typeof rendered === 'string' ? /*#__PURE__*/ (0, $cZGjp$react).createElement((0, $42dd7396e689e4e6$export$5f1af8db9871e1d6), null, rendered) : rendered;
    let isKeyboardModality = (0, $cZGjp$isFocusVisible)();
    return /*#__PURE__*/ (0, $cZGjp$react).createElement((0, $cZGjp$FocusRing), {
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cZGjp$menu_vars_cssmjs))), 'focus-ring')
    }, /*#__PURE__*/ (0, $cZGjp$react).createElement(ElementType, {
        ...(0, $cZGjp$mergeProps)(optionProps, shouldFocusOnHover ? {} : hoverProps),
        ref: ref,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cZGjp$menu_vars_cssmjs))), 'spectrum-Menu-item', {
            // If using virtual focus, apply focused styles to the item when the user is interacting with keyboard modality
            'is-focused': shouldUseVirtualFocus && isFocused && isKeyboardModality,
            'is-disabled': isDisabled,
            'is-selected': isSelected,
            'is-selectable': state.selectionManager.selectionMode !== 'none',
            // When shouldFocusOnHover is false, apply hover styles both when hovered with the mouse.
            // Otherwise, apply hover styles when focused using non-keyboard modality.
            'is-hovered': isHovered && !shouldFocusOnHover || isFocused && !isKeyboardModality
        })
    }, /*#__PURE__*/ (0, $cZGjp$react).createElement((0, $727c1a1d9e8b8d73$export$ef2184bd89960b14), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cZGjp$menu_vars_cssmjs))), 'spectrum-Menu-itemGrid')
    }, /*#__PURE__*/ (0, $cZGjp$react).createElement((0, $68f4bc2c1abc5618$export$ceb145244332b7a2), null, /*#__PURE__*/ (0, $cZGjp$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: {
            text: {
                UNSAFE_className: (0, ($parcel$interopDefault($cZGjp$menu_vars_cssmjs)))['spectrum-Menu-itemLabel'],
                ...labelProps
            },
            icon: {
                size: 'S',
                UNSAFE_className: (0, ($parcel$interopDefault($cZGjp$menu_vars_cssmjs)))['spectrum-Menu-icon']
            },
            avatar: {
                size: 'avatar-size-100',
                UNSAFE_className: (0, ($parcel$interopDefault($cZGjp$menu_vars_cssmjs)))['spectrum-Menu-avatar']
            },
            description: {
                UNSAFE_className: (0, ($parcel$interopDefault($cZGjp$menu_vars_cssmjs)))['spectrum-Menu-description'],
                ...descriptionProps
            }
        }
    }, contents, isSelected && /*#__PURE__*/ (0, $cZGjp$react).createElement((0, $cZGjp$spectrumiconsuiCheckmarkMedium), {
        slot: "checkmark",
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cZGjp$menu_vars_cssmjs))), 'spectrum-Menu-checkmark')
    }))))));
}


export {$1ff51a8d0dceabe5$export$feb3b6b552c14a12 as ListBoxOption};
//# sourceMappingURL=ListBoxOption.js.map
