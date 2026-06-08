import {ActionButton as $b41412308e87d8d9$export$cfc7921d29ef7b80} from "../button/ActionButton.mjs";
import $6SoYO$intlStringsmjs from "./intlStrings.mjs";
import {Menu as $8cccdb0b63bfcdeb$export$d9b273488cd8ce6f} from "./Menu.mjs";
import {MenuTrigger as $9928637078ff3033$export$27d2ad3c5815583e} from "./MenuTrigger.mjs";
import {useSlotProps as $62024859ff9f1f8a$export$1e5c9e6e4e15efe3} from "../utils/Slots.mjs";
import {filterDOMProps as $6SoYO$filterDOMProps} from "react-aria/filterDOMProps";
import $6SoYO$spectrumiconsworkflowMore from "@spectrum-icons/workflow/More";
import $6SoYO$react, {forwardRef as $6SoYO$forwardRef} from "react";
import {useLocalizedStringFormatter as $6SoYO$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
/*
 * Copyright 2021 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 








const $2f095355162c8dee$export$ed57a210b9d13bb6 = /*#__PURE__*/ (0, $6SoYO$forwardRef)(function ActionMenu(props, ref) {
    props = (0, $62024859ff9f1f8a$export$1e5c9e6e4e15efe3)(props, 'actionMenu');
    let stringFormatter = (0, $6SoYO$useLocalizedStringFormatter)((0, ($parcel$interopDefault($6SoYO$intlStringsmjs))), '@react-spectrum/menu');
    let buttonProps = (0, $6SoYO$filterDOMProps)(props, {
        labelable: true
    });
    if (buttonProps['aria-label'] === undefined) buttonProps['aria-label'] = stringFormatter.format('moreActions');
    return /*#__PURE__*/ (0, $6SoYO$react).createElement((0, $9928637078ff3033$export$27d2ad3c5815583e), {
        isOpen: props.isOpen,
        defaultOpen: props.defaultOpen,
        onOpenChange: props.onOpenChange,
        align: props.align,
        direction: props.direction,
        shouldFlip: props.shouldFlip
    }, /*#__PURE__*/ (0, $6SoYO$react).createElement((0, $b41412308e87d8d9$export$cfc7921d29ef7b80), {
        ref: ref,
        ...props,
        ...buttonProps
    }, /*#__PURE__*/ (0, $6SoYO$react).createElement((0, $6SoYO$spectrumiconsworkflowMore), null)), /*#__PURE__*/ (0, $6SoYO$react).createElement((0, $8cccdb0b63bfcdeb$export$d9b273488cd8ce6f), {
        children: props.children,
        items: props.items,
        disabledKeys: props.disabledKeys,
        onAction: props.onAction
    }));
});


export {$2f095355162c8dee$export$ed57a210b9d13bb6 as ActionMenu};
//# sourceMappingURL=ActionMenu.mjs.map
