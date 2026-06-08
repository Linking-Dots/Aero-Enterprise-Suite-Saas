import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ClearSlots as $62024859ff9f1f8a$export$ceb145244332b7a2, SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686} from "../utils/Slots.mjs";
import {Grid as $572f9fec526c2697$export$ef2184bd89960b14} from "../layout/Grid.mjs";
import {ListBoxContext as $200f4a7d8a01d3bb$export$7ff8f37d2d81a48d} from "./ListBoxContext.mjs";
import "../menu_vars.css";
import $4InsG$menu_vars_cssmjs from "../menu_vars_css.mjs";
import {Text as $f8cc90fea9436c19$export$5f1af8db9871e1d6} from "../text/Text.mjs";
import $4InsG$spectrumiconsuiCheckmarkMedium from "@spectrum-icons/ui/CheckmarkMedium";
import {FocusRing as $4InsG$FocusRing} from "react-aria/FocusRing";
import {isFocusVisible as $4InsG$isFocusVisible} from "react-aria/private/interactions/useFocusVisible";
import {mergeProps as $4InsG$mergeProps} from "react-aria/mergeProps";
import $4InsG$react, {useContext as $4InsG$useContext, useRef as $4InsG$useRef} from "react";
import {useHover as $4InsG$useHover} from "react-aria/useHover";
import {useOption as $4InsG$useOption} from "react-aria/useListBox";


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












function $17e2a30506a3150c$export$feb3b6b552c14a12(props) {
    let { item: item } = props;
    let { rendered: rendered, key: key } = item;
    let ElementType = item.props.href ? 'a' : 'div';
    let { state: state, shouldFocusOnHover: shouldFocusOnHover, shouldUseVirtualFocus: shouldUseVirtualFocus } = (0, $4InsG$useContext)((0, $200f4a7d8a01d3bb$export$7ff8f37d2d81a48d));
    let ref = (0, $4InsG$useRef)(undefined);
    let { optionProps: optionProps, labelProps: labelProps, descriptionProps: descriptionProps, isSelected: isSelected, isDisabled: isDisabled, isFocused: isFocused } = (0, $4InsG$useOption)({
        'aria-label': item['aria-label'],
        key: key,
        isVirtualized: true
    }, state, ref);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $4InsG$useHover)({
        ...props,
        isDisabled: isDisabled
    });
    let contents = typeof rendered === 'string' ? /*#__PURE__*/ (0, $4InsG$react).createElement((0, $f8cc90fea9436c19$export$5f1af8db9871e1d6), null, rendered) : rendered;
    let isKeyboardModality = (0, $4InsG$isFocusVisible)();
    return /*#__PURE__*/ (0, $4InsG$react).createElement((0, $4InsG$FocusRing), {
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4InsG$menu_vars_cssmjs))), 'focus-ring')
    }, /*#__PURE__*/ (0, $4InsG$react).createElement(ElementType, {
        ...(0, $4InsG$mergeProps)(optionProps, shouldFocusOnHover ? {} : hoverProps),
        ref: ref,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4InsG$menu_vars_cssmjs))), 'spectrum-Menu-item', {
            // If using virtual focus, apply focused styles to the item when the user is interacting with keyboard modality
            'is-focused': shouldUseVirtualFocus && isFocused && isKeyboardModality,
            'is-disabled': isDisabled,
            'is-selected': isSelected,
            'is-selectable': state.selectionManager.selectionMode !== 'none',
            // When shouldFocusOnHover is false, apply hover styles both when hovered with the mouse.
            // Otherwise, apply hover styles when focused using non-keyboard modality.
            'is-hovered': isHovered && !shouldFocusOnHover || isFocused && !isKeyboardModality
        })
    }, /*#__PURE__*/ (0, $4InsG$react).createElement((0, $572f9fec526c2697$export$ef2184bd89960b14), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4InsG$menu_vars_cssmjs))), 'spectrum-Menu-itemGrid')
    }, /*#__PURE__*/ (0, $4InsG$react).createElement((0, $62024859ff9f1f8a$export$ceb145244332b7a2), null, /*#__PURE__*/ (0, $4InsG$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: {
            text: {
                UNSAFE_className: (0, ($parcel$interopDefault($4InsG$menu_vars_cssmjs)))['spectrum-Menu-itemLabel'],
                ...labelProps
            },
            icon: {
                size: 'S',
                UNSAFE_className: (0, ($parcel$interopDefault($4InsG$menu_vars_cssmjs)))['spectrum-Menu-icon']
            },
            avatar: {
                size: 'avatar-size-100',
                UNSAFE_className: (0, ($parcel$interopDefault($4InsG$menu_vars_cssmjs)))['spectrum-Menu-avatar']
            },
            description: {
                UNSAFE_className: (0, ($parcel$interopDefault($4InsG$menu_vars_cssmjs)))['spectrum-Menu-description'],
                ...descriptionProps
            }
        }
    }, contents, isSelected && /*#__PURE__*/ (0, $4InsG$react).createElement((0, $4InsG$spectrumiconsuiCheckmarkMedium), {
        slot: "checkmark",
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4InsG$menu_vars_cssmjs))), 'spectrum-Menu-checkmark')
    }))))));
}


export {$17e2a30506a3150c$export$feb3b6b552c14a12 as ListBoxOption};
//# sourceMappingURL=ListBoxOption.mjs.map
