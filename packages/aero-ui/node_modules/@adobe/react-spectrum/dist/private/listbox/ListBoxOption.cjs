var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $d6479700d21b596b$exports = require("../layout/Grid.cjs");
var $b02f16a34f83c86b$exports = require("./ListBoxContext.cjs");
require("../menu_vars.css");
var $35d34152ff885d5c$exports = require("../menu_vars_css.cjs");
var $15e3b68ec42125a9$exports = require("../text/Text.cjs");
var $8CSP6$spectrumiconsuiCheckmarkMedium = require("@spectrum-icons/ui/CheckmarkMedium");
var $8CSP6$reactariaFocusRing = require("react-aria/FocusRing");
var $8CSP6$reactariaprivateinteractionsuseFocusVisible = require("react-aria/private/interactions/useFocusVisible");
var $8CSP6$reactariamergeProps = require("react-aria/mergeProps");
var $8CSP6$react = require("react");
var $8CSP6$reactariauseHover = require("react-aria/useHover");
var $8CSP6$reactariauseListBox = require("react-aria/useListBox");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ListBoxOption", function () { return $297e061fd2890b3d$export$feb3b6b552c14a12; });
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












function $297e061fd2890b3d$export$feb3b6b552c14a12(props) {
    let { item: item } = props;
    let { rendered: rendered, key: key } = item;
    let ElementType = item.props.href ? 'a' : 'div';
    let { state: state, shouldFocusOnHover: shouldFocusOnHover, shouldUseVirtualFocus: shouldUseVirtualFocus } = (0, $8CSP6$react.useContext)((0, $b02f16a34f83c86b$exports.ListBoxContext));
    let ref = (0, $8CSP6$react.useRef)(undefined);
    let { optionProps: optionProps, labelProps: labelProps, descriptionProps: descriptionProps, isSelected: isSelected, isDisabled: isDisabled, isFocused: isFocused } = (0, $8CSP6$reactariauseListBox.useOption)({
        'aria-label': item['aria-label'],
        key: key,
        isVirtualized: true
    }, state, ref);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $8CSP6$reactariauseHover.useHover)({
        ...props,
        isDisabled: isDisabled
    });
    let contents = typeof rendered === 'string' ? /*#__PURE__*/ (0, ($parcel$interopDefault($8CSP6$react))).createElement((0, $15e3b68ec42125a9$exports.Text), null, rendered) : rendered;
    let isKeyboardModality = (0, $8CSP6$reactariaprivateinteractionsuseFocusVisible.isFocusVisible)();
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8CSP6$react))).createElement((0, $8CSP6$reactariaFocusRing.FocusRing), {
        focusRingClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'focus-ring')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8CSP6$react))).createElement(ElementType, {
        ...(0, $8CSP6$reactariamergeProps.mergeProps)(optionProps, shouldFocusOnHover ? {} : hoverProps),
        ref: ref,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Menu-item', {
            // If using virtual focus, apply focused styles to the item when the user is interacting with keyboard modality
            'is-focused': shouldUseVirtualFocus && isFocused && isKeyboardModality,
            'is-disabled': isDisabled,
            'is-selected': isSelected,
            'is-selectable': state.selectionManager.selectionMode !== 'none',
            // When shouldFocusOnHover is false, apply hover styles both when hovered with the mouse.
            // Otherwise, apply hover styles when focused using non-keyboard modality.
            'is-hovered': isHovered && !shouldFocusOnHover || isFocused && !isKeyboardModality
        })
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8CSP6$react))).createElement((0, $d6479700d21b596b$exports.Grid), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Menu-itemGrid')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8CSP6$react))).createElement((0, $feede71cddc0c5f3$exports.ClearSlots), null, /*#__PURE__*/ (0, ($parcel$interopDefault($8CSP6$react))).createElement((0, $feede71cddc0c5f3$exports.SlotProvider), {
        slots: {
            text: {
                UNSAFE_className: (0, ($parcel$interopDefault($35d34152ff885d5c$exports)))['spectrum-Menu-itemLabel'],
                ...labelProps
            },
            icon: {
                size: 'S',
                UNSAFE_className: (0, ($parcel$interopDefault($35d34152ff885d5c$exports)))['spectrum-Menu-icon']
            },
            avatar: {
                size: 'avatar-size-100',
                UNSAFE_className: (0, ($parcel$interopDefault($35d34152ff885d5c$exports)))['spectrum-Menu-avatar']
            },
            description: {
                UNSAFE_className: (0, ($parcel$interopDefault($35d34152ff885d5c$exports)))['spectrum-Menu-description'],
                ...descriptionProps
            }
        }
    }, contents, isSelected && /*#__PURE__*/ (0, ($parcel$interopDefault($8CSP6$react))).createElement((0, ($parcel$interopDefault($8CSP6$spectrumiconsuiCheckmarkMedium))), {
        slot: "checkmark",
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Menu-checkmark')
    }))))));
}


//# sourceMappingURL=ListBoxOption.cjs.map
