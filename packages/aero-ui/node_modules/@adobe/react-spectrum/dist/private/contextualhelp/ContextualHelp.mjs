import {ActionButton as $b41412308e87d8d9$export$cfc7921d29ef7b80} from "../button/ActionButton.mjs";
import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ClearSlots as $62024859ff9f1f8a$export$ceb145244332b7a2, SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686} from "../utils/Slots.mjs";
import {Dialog as $8054558191a4f1c9$export$3ddf2d174ce01153} from "../dialog/Dialog.mjs";
import {DialogTrigger as $41e0ad2a982a34c5$export$2e1e1122cf0cba88} from "../dialog/DialogTrigger.mjs";
import "../contextualhelp_vars.css";
import $kOhjZ$contextualhelp_vars_cssmjs from "../contextualhelp_vars_css.mjs";
import $kOhjZ$intlStringsmjs from "./intlStrings.mjs";
import $kOhjZ$spectrumiconsworkflowHelpOutline from "@spectrum-icons/workflow/HelpOutline";
import $kOhjZ$spectrumiconsworkflowInfoOutline from "@spectrum-icons/workflow/InfoOutline";
import {mergeProps as $kOhjZ$mergeProps} from "react-aria/mergeProps";
import $kOhjZ$react from "react";
import {useLabels as $kOhjZ$useLabels} from "react-aria/private/utils/useLabels";
import {useLocalizedStringFormatter as $kOhjZ$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


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












const $555aed5568075ed2$export$7d3cdb256c2ba320 = /*#__PURE__*/ (0, $kOhjZ$react).forwardRef(function ContextualHelp(props, ref) {
    let { variant: variant = 'help', placement: placement = 'bottom start', children: children, ...otherProps } = props;
    let stringFormatter = (0, $kOhjZ$useLocalizedStringFormatter)((0, ($parcel$interopDefault($kOhjZ$intlStringsmjs))), '@react-spectrum/contextualhelp');
    let icon = variant === 'info' ? /*#__PURE__*/ (0, $kOhjZ$react).createElement((0, $kOhjZ$spectrumiconsworkflowInfoOutline), null) : /*#__PURE__*/ (0, $kOhjZ$react).createElement((0, $kOhjZ$spectrumiconsworkflowHelpOutline), null);
    let slots = {
        content: {
            UNSAFE_className: (0, ($parcel$interopDefault($kOhjZ$contextualhelp_vars_cssmjs)))['react-spectrum-ContextualHelp-content']
        },
        footer: {
            UNSAFE_className: (0, ($parcel$interopDefault($kOhjZ$contextualhelp_vars_cssmjs)))['react-spectrum-ContextualHelp-footer']
        }
    };
    let labelProps = (0, $kOhjZ$useLabels)(otherProps, stringFormatter.format(variant));
    return /*#__PURE__*/ (0, $kOhjZ$react).createElement((0, $41e0ad2a982a34c5$export$2e1e1122cf0cba88), {
        ...otherProps,
        type: "popover",
        placement: placement,
        hideArrow: true
    }, /*#__PURE__*/ (0, $kOhjZ$react).createElement((0, $b41412308e87d8d9$export$cfc7921d29ef7b80), {
        ...(0, $kOhjZ$mergeProps)(otherProps, labelProps, {
            isDisabled: false
        }),
        ref: ref,
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($kOhjZ$contextualhelp_vars_cssmjs))), 'react-spectrum-ContextualHelp-button', otherProps.UNSAFE_className),
        isQuiet: true
    }, icon), /*#__PURE__*/ (0, $kOhjZ$react).createElement((0, $62024859ff9f1f8a$export$ceb145244332b7a2), null, /*#__PURE__*/ (0, $kOhjZ$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: slots
    }, /*#__PURE__*/ (0, $kOhjZ$react).createElement((0, $8054558191a4f1c9$export$3ddf2d174ce01153), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($kOhjZ$contextualhelp_vars_cssmjs))), 'react-spectrum-ContextualHelp-dialog')
    }, children))));
});


export {$555aed5568075ed2$export$7d3cdb256c2ba320 as ContextualHelp};
//# sourceMappingURL=ContextualHelp.mjs.map
