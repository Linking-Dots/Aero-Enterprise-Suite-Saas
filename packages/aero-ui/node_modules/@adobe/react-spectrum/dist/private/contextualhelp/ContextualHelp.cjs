var $183c5173677598aa$exports = require("../button/ActionButton.cjs");
var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $db50fa4488be370e$exports = require("../dialog/Dialog.cjs");
var $d4a85248c617d550$exports = require("../dialog/DialogTrigger.cjs");
require("../contextualhelp_vars.css");
var $09ef91de04df24e0$exports = require("../contextualhelp_vars_css.cjs");
var $5fb0bf5c6aeacd74$exports = require("./intlStrings.cjs");
var $diESa$spectrumiconsworkflowHelpOutline = require("@spectrum-icons/workflow/HelpOutline");
var $diESa$spectrumiconsworkflowInfoOutline = require("@spectrum-icons/workflow/InfoOutline");
var $diESa$reactariamergeProps = require("react-aria/mergeProps");
var $diESa$react = require("react");
var $diESa$reactariaprivateutilsuseLabels = require("react-aria/private/utils/useLabels");
var $diESa$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ContextualHelp", function () { return $d12d6d5847a946a3$export$7d3cdb256c2ba320; });
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












const $d12d6d5847a946a3$export$7d3cdb256c2ba320 = /*#__PURE__*/ (0, ($parcel$interopDefault($diESa$react))).forwardRef(function ContextualHelp(props, ref) {
    let { variant: variant = 'help', placement: placement = 'bottom start', children: children, ...otherProps } = props;
    let stringFormatter = (0, $diESa$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($5fb0bf5c6aeacd74$exports))), '@react-spectrum/contextualhelp');
    let icon = variant === 'info' ? /*#__PURE__*/ (0, ($parcel$interopDefault($diESa$react))).createElement((0, ($parcel$interopDefault($diESa$spectrumiconsworkflowInfoOutline))), null) : /*#__PURE__*/ (0, ($parcel$interopDefault($diESa$react))).createElement((0, ($parcel$interopDefault($diESa$spectrumiconsworkflowHelpOutline))), null);
    let slots = {
        content: {
            UNSAFE_className: (0, ($parcel$interopDefault($09ef91de04df24e0$exports)))['react-spectrum-ContextualHelp-content']
        },
        footer: {
            UNSAFE_className: (0, ($parcel$interopDefault($09ef91de04df24e0$exports)))['react-spectrum-ContextualHelp-footer']
        }
    };
    let labelProps = (0, $diESa$reactariaprivateutilsuseLabels.useLabels)(otherProps, stringFormatter.format(variant));
    return /*#__PURE__*/ (0, ($parcel$interopDefault($diESa$react))).createElement((0, $d4a85248c617d550$exports.DialogTrigger), {
        ...otherProps,
        type: "popover",
        placement: placement,
        hideArrow: true
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($diESa$react))).createElement((0, $183c5173677598aa$exports.ActionButton), {
        ...(0, $diESa$reactariamergeProps.mergeProps)(otherProps, labelProps, {
            isDisabled: false
        }),
        ref: ref,
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($09ef91de04df24e0$exports))), 'react-spectrum-ContextualHelp-button', otherProps.UNSAFE_className),
        isQuiet: true
    }, icon), /*#__PURE__*/ (0, ($parcel$interopDefault($diESa$react))).createElement((0, $feede71cddc0c5f3$exports.ClearSlots), null, /*#__PURE__*/ (0, ($parcel$interopDefault($diESa$react))).createElement((0, $feede71cddc0c5f3$exports.SlotProvider), {
        slots: slots
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($diESa$react))).createElement((0, $db50fa4488be370e$exports.Dialog), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($09ef91de04df24e0$exports))), 'react-spectrum-ContextualHelp-dialog')
    }, children))));
});


//# sourceMappingURL=ContextualHelp.cjs.map
