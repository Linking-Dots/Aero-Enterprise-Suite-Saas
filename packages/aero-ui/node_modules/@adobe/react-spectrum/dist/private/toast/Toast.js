import {Button as $88f996b7df3752a1$export$353f5b6fc5456de1} from "../button/Button.js";
import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {ClearButton as $cf8b586db4c34baa$export$13ec83e50bf04290} from "../button/ClearButton.js";
import $aifTz$intlStringsjs from "./intlStrings.js";
import "../toast_vars.css";
import $aifTz$toast_vars_cssmjs from "../toast_vars_css.mjs";
import "./toastContainer.css";
import $aifTz$toastContainer_cssmjs from "./toastContainer_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import $aifTz$spectrumiconsuiAlertMedium from "@spectrum-icons/ui/AlertMedium";
import $aifTz$spectrumiconsuiCrossMedium from "@spectrum-icons/ui/CrossMedium";
import {filterDOMProps as $aifTz$filterDOMProps} from "react-aria/filterDOMProps";
import $aifTz$spectrumiconsuiInfoMedium from "@spectrum-icons/ui/InfoMedium";
import {mergeProps as $aifTz$mergeProps} from "react-aria/mergeProps";
import $aifTz$react from "react";
import $aifTz$spectrumiconsuiSuccessMedium from "@spectrum-icons/ui/SuccessMedium";
import {useFocusRing as $aifTz$useFocusRing} from "react-aria/useFocusRing";
import {useLocalizedStringFormatter as $aifTz$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";
import {useToast as $aifTz$useToast} from "react-aria/useToast";


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

















const $5ecef0f995bde443$export$fde44257752a9f60 = {
    info: (0, $aifTz$spectrumiconsuiInfoMedium),
    negative: (0, $aifTz$spectrumiconsuiAlertMedium),
    positive: (0, $aifTz$spectrumiconsuiSuccessMedium)
};
const $5ecef0f995bde443$export$8d8dc7d5f743331b = /*#__PURE__*/ (0, $aifTz$react).forwardRef(function Toast(props, ref) {
    let { toast: { key: key, content: { children: children, variant: variant, actionLabel: actionLabel, onAction: onAction, shouldCloseOnAction: shouldCloseOnAction } }, state: state, ...otherProps } = props;
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let { closeButtonProps: closeButtonProps, titleProps: titleProps, toastProps: toastProps, contentProps: contentProps } = (0, $aifTz$useToast)(props, state, domRef);
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let stringFormatter = (0, $aifTz$useLocalizedStringFormatter)((0, ($parcel$interopDefault($aifTz$intlStringsjs))), '@react-spectrum/toast');
    let iconLabel = variant && variant !== 'neutral' ? stringFormatter.format(variant) : null;
    let Icon = $5ecef0f995bde443$export$fde44257752a9f60[variant];
    let { isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $aifTz$useFocusRing)();
    const handleAction = ()=>{
        if (onAction) onAction();
        if (shouldCloseOnAction) state.close(key);
    };
    return /*#__PURE__*/ (0, $aifTz$react).createElement("div", {
        ...styleProps,
        ...(0, $aifTz$mergeProps)(toastProps, focusProps),
        ...(0, $aifTz$filterDOMProps)(props.toast.content),
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aifTz$toast_vars_cssmjs))), 'spectrum-Toast', {
            ['spectrum-Toast--' + variant]: variant
        }, styleProps.className, (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aifTz$toastContainer_cssmjs))), 'spectrum-Toast', {
            'focus-ring': isFocusVisible
        }))
    }, /*#__PURE__*/ (0, $aifTz$react).createElement("div", {
        ...contentProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aifTz$toastContainer_cssmjs))), 'spectrum-Toast-contentWrapper')
    }, Icon && /*#__PURE__*/ (0, $aifTz$react).createElement(Icon, {
        "aria-label": iconLabel,
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aifTz$toast_vars_cssmjs))), 'spectrum-Toast-typeIcon')
    }), /*#__PURE__*/ (0, $aifTz$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aifTz$toast_vars_cssmjs))), 'spectrum-Toast-body'),
        role: "presentation"
    }, /*#__PURE__*/ (0, $aifTz$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aifTz$toast_vars_cssmjs))), 'spectrum-Toast-content'),
        role: "presentation",
        ...titleProps
    }, children), actionLabel && /*#__PURE__*/ (0, $aifTz$react).createElement((0, $88f996b7df3752a1$export$353f5b6fc5456de1), {
        onPress: handleAction,
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aifTz$toast_vars_cssmjs))), 'spectrum-Button'),
        variant: "secondary",
        staticColor: "white",
        "data-testid": "rsp-Toast-secondaryButton"
    }, actionLabel))), /*#__PURE__*/ (0, $aifTz$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aifTz$toast_vars_cssmjs))), 'spectrum-Toast-buttons')
    }, /*#__PURE__*/ (0, $aifTz$react).createElement((0, $cf8b586db4c34baa$export$13ec83e50bf04290), {
        ...closeButtonProps,
        variant: "overBackground",
        "data-testid": "rsp-Toast-closeButton"
    }, /*#__PURE__*/ (0, $aifTz$react).createElement((0, $aifTz$spectrumiconsuiCrossMedium), null))));
});


export {$5ecef0f995bde443$export$fde44257752a9f60 as ICONS, $5ecef0f995bde443$export$8d8dc7d5f743331b as Toast};
//# sourceMappingURL=Toast.js.map
