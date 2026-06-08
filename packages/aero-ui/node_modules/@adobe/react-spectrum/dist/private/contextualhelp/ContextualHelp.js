import {ActionButton as $c265dbb41bfd0210$export$cfc7921d29ef7b80} from "../button/ActionButton.js";
import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {ClearSlots as $68f4bc2c1abc5618$export$ceb145244332b7a2, SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686} from "../utils/Slots.js";
import {Dialog as $89418a3659cad0c7$export$3ddf2d174ce01153} from "../dialog/Dialog.js";
import {DialogTrigger as $bcff05049955156f$export$2e1e1122cf0cba88} from "../dialog/DialogTrigger.js";
import "../contextualhelp_vars.css";
import $lmsT3$contextualhelp_vars_cssmjs from "../contextualhelp_vars_css.mjs";
import $lmsT3$intlStringsjs from "./intlStrings.js";
import $lmsT3$spectrumiconsworkflowHelpOutline from "@spectrum-icons/workflow/HelpOutline";
import $lmsT3$spectrumiconsworkflowInfoOutline from "@spectrum-icons/workflow/InfoOutline";
import {mergeProps as $lmsT3$mergeProps} from "react-aria/mergeProps";
import $lmsT3$react from "react";
import {useLabels as $lmsT3$useLabels} from "react-aria/private/utils/useLabels";
import {useLocalizedStringFormatter as $lmsT3$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


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












const $e74df8e6258adf4e$export$7d3cdb256c2ba320 = /*#__PURE__*/ (0, $lmsT3$react).forwardRef(function ContextualHelp(props, ref) {
    let { variant: variant = 'help', placement: placement = 'bottom start', children: children, ...otherProps } = props;
    let stringFormatter = (0, $lmsT3$useLocalizedStringFormatter)((0, ($parcel$interopDefault($lmsT3$intlStringsjs))), '@react-spectrum/contextualhelp');
    let icon = variant === 'info' ? /*#__PURE__*/ (0, $lmsT3$react).createElement((0, $lmsT3$spectrumiconsworkflowInfoOutline), null) : /*#__PURE__*/ (0, $lmsT3$react).createElement((0, $lmsT3$spectrumiconsworkflowHelpOutline), null);
    let slots = {
        content: {
            UNSAFE_className: (0, ($parcel$interopDefault($lmsT3$contextualhelp_vars_cssmjs)))['react-spectrum-ContextualHelp-content']
        },
        footer: {
            UNSAFE_className: (0, ($parcel$interopDefault($lmsT3$contextualhelp_vars_cssmjs)))['react-spectrum-ContextualHelp-footer']
        }
    };
    let labelProps = (0, $lmsT3$useLabels)(otherProps, stringFormatter.format(variant));
    return /*#__PURE__*/ (0, $lmsT3$react).createElement((0, $bcff05049955156f$export$2e1e1122cf0cba88), {
        ...otherProps,
        type: "popover",
        placement: placement,
        hideArrow: true
    }, /*#__PURE__*/ (0, $lmsT3$react).createElement((0, $c265dbb41bfd0210$export$cfc7921d29ef7b80), {
        ...(0, $lmsT3$mergeProps)(otherProps, labelProps, {
            isDisabled: false
        }),
        ref: ref,
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lmsT3$contextualhelp_vars_cssmjs))), 'react-spectrum-ContextualHelp-button', otherProps.UNSAFE_className),
        isQuiet: true
    }, icon), /*#__PURE__*/ (0, $lmsT3$react).createElement((0, $68f4bc2c1abc5618$export$ceb145244332b7a2), null, /*#__PURE__*/ (0, $lmsT3$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: slots
    }, /*#__PURE__*/ (0, $lmsT3$react).createElement((0, $89418a3659cad0c7$export$3ddf2d174ce01153), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lmsT3$contextualhelp_vars_cssmjs))), 'react-spectrum-ContextualHelp-dialog')
    }, children))));
});


export {$e74df8e6258adf4e$export$7d3cdb256c2ba320 as ContextualHelp};
//# sourceMappingURL=ContextualHelp.js.map
