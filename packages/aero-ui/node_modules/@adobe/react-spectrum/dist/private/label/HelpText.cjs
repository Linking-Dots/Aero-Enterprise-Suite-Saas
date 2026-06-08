var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../helptext_vars.css");
var $5385de30f6367abd$exports = require("../helptext_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $4Q1BD$spectrumiconsuiAlertMedium = require("@spectrum-icons/ui/AlertMedium");
var $4Q1BD$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "HelpText", function () { return $2b77b98944e1735c$export$a67c0bc59081311a; });
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





const $2b77b98944e1735c$export$a67c0bc59081311a = /*#__PURE__*/ (0, ($parcel$interopDefault($4Q1BD$react))).forwardRef(function HelpText(props, ref) {
    let { description: description, errorMessage: errorMessage, validationState: validationState, isInvalid: isInvalid, isDisabled: isDisabled, showErrorIcon: showErrorIcon, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps } = props;
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let isErrorMessage = errorMessage && (isInvalid || validationState === 'invalid');
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($4Q1BD$react))).createElement("div", {
        ...styleProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($5385de30f6367abd$exports))), 'spectrum-HelpText', `spectrum-HelpText--${isErrorMessage ? 'negative' : 'neutral'}`, {
            'is-disabled': isDisabled
        }, styleProps.className),
        ref: domRef
    }, isErrorMessage ? /*#__PURE__*/ (0, ($parcel$interopDefault($4Q1BD$react))).createElement((0, ($parcel$interopDefault($4Q1BD$react))).Fragment, null, showErrorIcon && /*#__PURE__*/ (0, ($parcel$interopDefault($4Q1BD$react))).createElement((0, ($parcel$interopDefault($4Q1BD$spectrumiconsuiAlertMedium))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($5385de30f6367abd$exports))), 'spectrum-HelpText-validationIcon')
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($4Q1BD$react))).createElement("div", {
        ...errorMessageProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($5385de30f6367abd$exports))), 'spectrum-HelpText-text')
    }, errorMessage)) : /*#__PURE__*/ (0, ($parcel$interopDefault($4Q1BD$react))).createElement("div", {
        ...descriptionProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($5385de30f6367abd$exports))), 'spectrum-HelpText-text')
    }, description));
});


//# sourceMappingURL=HelpText.cjs.map
