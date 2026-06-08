import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../helptext_vars.css";
import $4fuex$helptext_vars_cssmjs from "../helptext_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import $4fuex$spectrumiconsuiAlertMedium from "@spectrum-icons/ui/AlertMedium";
import $4fuex$react from "react";


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





const $ef3f0b17611eb293$export$a67c0bc59081311a = /*#__PURE__*/ (0, $4fuex$react).forwardRef(function HelpText(props, ref) {
    let { description: description, errorMessage: errorMessage, validationState: validationState, isInvalid: isInvalid, isDisabled: isDisabled, showErrorIcon: showErrorIcon, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps } = props;
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let isErrorMessage = errorMessage && (isInvalid || validationState === 'invalid');
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    return /*#__PURE__*/ (0, $4fuex$react).createElement("div", {
        ...styleProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4fuex$helptext_vars_cssmjs))), 'spectrum-HelpText', `spectrum-HelpText--${isErrorMessage ? 'negative' : 'neutral'}`, {
            'is-disabled': isDisabled
        }, styleProps.className),
        ref: domRef
    }, isErrorMessage ? /*#__PURE__*/ (0, $4fuex$react).createElement((0, $4fuex$react).Fragment, null, showErrorIcon && /*#__PURE__*/ (0, $4fuex$react).createElement((0, $4fuex$spectrumiconsuiAlertMedium), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4fuex$helptext_vars_cssmjs))), 'spectrum-HelpText-validationIcon')
    }), /*#__PURE__*/ (0, $4fuex$react).createElement("div", {
        ...errorMessageProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4fuex$helptext_vars_cssmjs))), 'spectrum-HelpText-text')
    }, errorMessage)) : /*#__PURE__*/ (0, $4fuex$react).createElement("div", {
        ...descriptionProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4fuex$helptext_vars_cssmjs))), 'spectrum-HelpText-text')
    }, description));
});


export {$ef3f0b17611eb293$export$a67c0bc59081311a as HelpText};
//# sourceMappingURL=HelpText.mjs.map
