import {ActionButton as $c265dbb41bfd0210$export$cfc7921d29ef7b80} from "../button/ActionButton.js";
import $8dxeo$intlStringsjs from "./intlStrings.js";
import {Menu as $79ddee63a726ea3d$export$d9b273488cd8ce6f} from "./Menu.js";
import {MenuTrigger as $9f6ebde23392f425$export$27d2ad3c5815583e} from "./MenuTrigger.js";
import {useSlotProps as $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3} from "../utils/Slots.js";
import {filterDOMProps as $8dxeo$filterDOMProps} from "react-aria/filterDOMProps";
import $8dxeo$spectrumiconsworkflowMore from "@spectrum-icons/workflow/More";
import $8dxeo$react, {forwardRef as $8dxeo$forwardRef} from "react";
import {useLocalizedStringFormatter as $8dxeo$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


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








const $db2bd940d589c867$export$ed57a210b9d13bb6 = /*#__PURE__*/ (0, $8dxeo$forwardRef)(function ActionMenu(props, ref) {
    props = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)(props, 'actionMenu');
    let stringFormatter = (0, $8dxeo$useLocalizedStringFormatter)((0, ($parcel$interopDefault($8dxeo$intlStringsjs))), '@react-spectrum/menu');
    let buttonProps = (0, $8dxeo$filterDOMProps)(props, {
        labelable: true
    });
    if (buttonProps['aria-label'] === undefined) buttonProps['aria-label'] = stringFormatter.format('moreActions');
    return /*#__PURE__*/ (0, $8dxeo$react).createElement((0, $9f6ebde23392f425$export$27d2ad3c5815583e), {
        isOpen: props.isOpen,
        defaultOpen: props.defaultOpen,
        onOpenChange: props.onOpenChange,
        align: props.align,
        direction: props.direction,
        shouldFlip: props.shouldFlip
    }, /*#__PURE__*/ (0, $8dxeo$react).createElement((0, $c265dbb41bfd0210$export$cfc7921d29ef7b80), {
        ref: ref,
        ...props,
        ...buttonProps
    }, /*#__PURE__*/ (0, $8dxeo$react).createElement((0, $8dxeo$spectrumiconsworkflowMore), null)), /*#__PURE__*/ (0, $8dxeo$react).createElement((0, $79ddee63a726ea3d$export$d9b273488cd8ce6f), {
        children: props.children,
        items: props.items,
        disabledKeys: props.disabledKeys,
        onAction: props.onAction
    }));
});


export {$db2bd940d589c867$export$ed57a210b9d13bb6 as ActionMenu};
//# sourceMappingURL=ActionMenu.js.map
