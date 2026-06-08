import {Button as $36a99e6d76bd9d26$export$353f5b6fc5456de1} from "../button/Button.mjs";
import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ClearButton as $ab14010a528467be$export$13ec83e50bf04290} from "../button/ClearButton.mjs";
import $Nd5l0$intlStringsmjs from "./intlStrings.mjs";
import "../toast_vars.css";
import $Nd5l0$toast_vars_cssmjs from "../toast_vars_css.mjs";
import "./toastContainer.css";
import $Nd5l0$toastContainer_cssmjs from "./toastContainer_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import $Nd5l0$spectrumiconsuiAlertMedium from "@spectrum-icons/ui/AlertMedium";
import $Nd5l0$spectrumiconsuiCrossMedium from "@spectrum-icons/ui/CrossMedium";
import {filterDOMProps as $Nd5l0$filterDOMProps} from "react-aria/filterDOMProps";
import $Nd5l0$spectrumiconsuiInfoMedium from "@spectrum-icons/ui/InfoMedium";
import {mergeProps as $Nd5l0$mergeProps} from "react-aria/mergeProps";
import $Nd5l0$react from "react";
import $Nd5l0$spectrumiconsuiSuccessMedium from "@spectrum-icons/ui/SuccessMedium";
import {useFocusRing as $Nd5l0$useFocusRing} from "react-aria/useFocusRing";
import {useLocalizedStringFormatter as $Nd5l0$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";
import {useToast as $Nd5l0$useToast} from "react-aria/useToast";


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

















const $cfebc782ec37c3c6$export$fde44257752a9f60 = {
    info: (0, $Nd5l0$spectrumiconsuiInfoMedium),
    negative: (0, $Nd5l0$spectrumiconsuiAlertMedium),
    positive: (0, $Nd5l0$spectrumiconsuiSuccessMedium)
};
const $cfebc782ec37c3c6$export$8d8dc7d5f743331b = /*#__PURE__*/ (0, $Nd5l0$react).forwardRef(function Toast(props, ref) {
    let { toast: { key: key, content: { children: children, variant: variant, actionLabel: actionLabel, onAction: onAction, shouldCloseOnAction: shouldCloseOnAction } }, state: state, ...otherProps } = props;
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let { closeButtonProps: closeButtonProps, titleProps: titleProps, toastProps: toastProps, contentProps: contentProps } = (0, $Nd5l0$useToast)(props, state, domRef);
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let stringFormatter = (0, $Nd5l0$useLocalizedStringFormatter)((0, ($parcel$interopDefault($Nd5l0$intlStringsmjs))), '@react-spectrum/toast');
    let iconLabel = variant && variant !== 'neutral' ? stringFormatter.format(variant) : null;
    let Icon = $cfebc782ec37c3c6$export$fde44257752a9f60[variant];
    let { isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $Nd5l0$useFocusRing)();
    const handleAction = ()=>{
        if (onAction) onAction();
        if (shouldCloseOnAction) state.close(key);
    };
    return /*#__PURE__*/ (0, $Nd5l0$react).createElement("div", {
        ...styleProps,
        ...(0, $Nd5l0$mergeProps)(toastProps, focusProps),
        ...(0, $Nd5l0$filterDOMProps)(props.toast.content),
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($Nd5l0$toast_vars_cssmjs))), 'spectrum-Toast', {
            ['spectrum-Toast--' + variant]: variant
        }, styleProps.className, (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($Nd5l0$toastContainer_cssmjs))), 'spectrum-Toast', {
            'focus-ring': isFocusVisible
        }))
    }, /*#__PURE__*/ (0, $Nd5l0$react).createElement("div", {
        ...contentProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($Nd5l0$toastContainer_cssmjs))), 'spectrum-Toast-contentWrapper')
    }, Icon && /*#__PURE__*/ (0, $Nd5l0$react).createElement(Icon, {
        "aria-label": iconLabel,
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($Nd5l0$toast_vars_cssmjs))), 'spectrum-Toast-typeIcon')
    }), /*#__PURE__*/ (0, $Nd5l0$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($Nd5l0$toast_vars_cssmjs))), 'spectrum-Toast-body'),
        role: "presentation"
    }, /*#__PURE__*/ (0, $Nd5l0$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($Nd5l0$toast_vars_cssmjs))), 'spectrum-Toast-content'),
        role: "presentation",
        ...titleProps
    }, children), actionLabel && /*#__PURE__*/ (0, $Nd5l0$react).createElement((0, $36a99e6d76bd9d26$export$353f5b6fc5456de1), {
        onPress: handleAction,
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($Nd5l0$toast_vars_cssmjs))), 'spectrum-Button'),
        variant: "secondary",
        staticColor: "white",
        "data-testid": "rsp-Toast-secondaryButton"
    }, actionLabel))), /*#__PURE__*/ (0, $Nd5l0$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($Nd5l0$toast_vars_cssmjs))), 'spectrum-Toast-buttons')
    }, /*#__PURE__*/ (0, $Nd5l0$react).createElement((0, $ab14010a528467be$export$13ec83e50bf04290), {
        ...closeButtonProps,
        variant: "overBackground",
        "data-testid": "rsp-Toast-closeButton"
    }, /*#__PURE__*/ (0, $Nd5l0$react).createElement((0, $Nd5l0$spectrumiconsuiCrossMedium), null))));
});


export {$cfebc782ec37c3c6$export$fde44257752a9f60 as ICONS, $cfebc782ec37c3c6$export$8d8dc7d5f743331b as Toast};
//# sourceMappingURL=Toast.mjs.map
