import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../helptext_vars.css";
import $jepyI$helptext_vars_cssmjs from "../helptext_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import $jepyI$spectrumiconsuiAlertMedium from "@spectrum-icons/ui/AlertMedium";
import $jepyI$react from "react";


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





const $a24709aa19b9016d$export$a67c0bc59081311a = /*#__PURE__*/ (0, $jepyI$react).forwardRef(function HelpText(props, ref) {
    let { description: description, errorMessage: errorMessage, validationState: validationState, isInvalid: isInvalid, isDisabled: isDisabled, showErrorIcon: showErrorIcon, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps } = props;
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let isErrorMessage = errorMessage && (isInvalid || validationState === 'invalid');
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    return /*#__PURE__*/ (0, $jepyI$react).createElement("div", {
        ...styleProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($jepyI$helptext_vars_cssmjs))), 'spectrum-HelpText', `spectrum-HelpText--${isErrorMessage ? 'negative' : 'neutral'}`, {
            'is-disabled': isDisabled
        }, styleProps.className),
        ref: domRef
    }, isErrorMessage ? /*#__PURE__*/ (0, $jepyI$react).createElement((0, $jepyI$react).Fragment, null, showErrorIcon && /*#__PURE__*/ (0, $jepyI$react).createElement((0, $jepyI$spectrumiconsuiAlertMedium), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($jepyI$helptext_vars_cssmjs))), 'spectrum-HelpText-validationIcon')
    }), /*#__PURE__*/ (0, $jepyI$react).createElement("div", {
        ...errorMessageProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($jepyI$helptext_vars_cssmjs))), 'spectrum-HelpText-text')
    }, errorMessage)) : /*#__PURE__*/ (0, $jepyI$react).createElement("div", {
        ...descriptionProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($jepyI$helptext_vars_cssmjs))), 'spectrum-HelpText-text')
    }, description));
});


export {$a24709aa19b9016d$export$a67c0bc59081311a as HelpText};
//# sourceMappingURL=HelpText.js.map
