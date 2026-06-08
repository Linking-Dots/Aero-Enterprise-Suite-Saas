var $183c5173677598aa$exports = require("../button/ActionButton.cjs");
var $e609f7c27f409c35$exports = require("./intlStrings.cjs");
var $802fb5441f76e7b0$exports = require("./Menu.cjs");
var $98227f5fd590c993$exports = require("./MenuTrigger.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $31bi0$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $31bi0$spectrumiconsworkflowMore = require("@spectrum-icons/workflow/More");
var $31bi0$react = require("react");
var $31bi0$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ActionMenu", function () { return $e420df9d394eecb6$export$ed57a210b9d13bb6; });
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








const $e420df9d394eecb6$export$ed57a210b9d13bb6 = /*#__PURE__*/ (0, $31bi0$react.forwardRef)(function ActionMenu(props, ref) {
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'actionMenu');
    let stringFormatter = (0, $31bi0$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($e609f7c27f409c35$exports))), '@react-spectrum/menu');
    let buttonProps = (0, $31bi0$reactariafilterDOMProps.filterDOMProps)(props, {
        labelable: true
    });
    if (buttonProps['aria-label'] === undefined) buttonProps['aria-label'] = stringFormatter.format('moreActions');
    return /*#__PURE__*/ (0, ($parcel$interopDefault($31bi0$react))).createElement((0, $98227f5fd590c993$exports.MenuTrigger), {
        isOpen: props.isOpen,
        defaultOpen: props.defaultOpen,
        onOpenChange: props.onOpenChange,
        align: props.align,
        direction: props.direction,
        shouldFlip: props.shouldFlip
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($31bi0$react))).createElement((0, $183c5173677598aa$exports.ActionButton), {
        ref: ref,
        ...props,
        ...buttonProps
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($31bi0$react))).createElement((0, ($parcel$interopDefault($31bi0$spectrumiconsworkflowMore))), null)), /*#__PURE__*/ (0, ($parcel$interopDefault($31bi0$react))).createElement((0, $802fb5441f76e7b0$exports.Menu), {
        children: props.children,
        items: props.items,
        disabledKeys: props.disabledKeys,
        onAction: props.onAction
    }));
});


//# sourceMappingURL=ActionMenu.cjs.map
