var $92256f4fe9ec9f59$exports = require("../button/Button.cjs");
var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $0fc8553a4214494f$exports = require("../button/ClearButton.cjs");
var $599fe3e935694113$exports = require("./intlStrings.cjs");
require("../toast_vars.css");
var $e4abcc5792370d9e$exports = require("../toast_vars_css.cjs");
require("./toastContainer.css");
var $1e451ff201076fe2$exports = require("./toastContainer_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $9Vw3a$spectrumiconsuiAlertMedium = require("@spectrum-icons/ui/AlertMedium");
var $9Vw3a$spectrumiconsuiCrossMedium = require("@spectrum-icons/ui/CrossMedium");
var $9Vw3a$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $9Vw3a$spectrumiconsuiInfoMedium = require("@spectrum-icons/ui/InfoMedium");
var $9Vw3a$reactariamergeProps = require("react-aria/mergeProps");
var $9Vw3a$react = require("react");
var $9Vw3a$spectrumiconsuiSuccessMedium = require("@spectrum-icons/ui/SuccessMedium");
var $9Vw3a$reactariauseFocusRing = require("react-aria/useFocusRing");
var $9Vw3a$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");
var $9Vw3a$reactariauseToast = require("react-aria/useToast");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Toast", function () { return $ca4ccf35c6998262$export$8d8dc7d5f743331b; });
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

















const $ca4ccf35c6998262$export$fde44257752a9f60 = {
    info: (0, ($parcel$interopDefault($9Vw3a$spectrumiconsuiInfoMedium))),
    negative: (0, ($parcel$interopDefault($9Vw3a$spectrumiconsuiAlertMedium))),
    positive: (0, ($parcel$interopDefault($9Vw3a$spectrumiconsuiSuccessMedium)))
};
const $ca4ccf35c6998262$export$8d8dc7d5f743331b = /*#__PURE__*/ (0, ($parcel$interopDefault($9Vw3a$react))).forwardRef(function Toast(props, ref) {
    let { toast: { key: key, content: { children: children, variant: variant, actionLabel: actionLabel, onAction: onAction, shouldCloseOnAction: shouldCloseOnAction } }, state: state, ...otherProps } = props;
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let { closeButtonProps: closeButtonProps, titleProps: titleProps, toastProps: toastProps, contentProps: contentProps } = (0, $9Vw3a$reactariauseToast.useToast)(props, state, domRef);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let stringFormatter = (0, $9Vw3a$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($599fe3e935694113$exports))), '@react-spectrum/toast');
    let iconLabel = variant && variant !== 'neutral' ? stringFormatter.format(variant) : null;
    let Icon = $ca4ccf35c6998262$export$fde44257752a9f60[variant];
    let { isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $9Vw3a$reactariauseFocusRing.useFocusRing)();
    const handleAction = ()=>{
        if (onAction) onAction();
        if (shouldCloseOnAction) state.close(key);
    };
    return /*#__PURE__*/ (0, ($parcel$interopDefault($9Vw3a$react))).createElement("div", {
        ...styleProps,
        ...(0, $9Vw3a$reactariamergeProps.mergeProps)(toastProps, focusProps),
        ...(0, $9Vw3a$reactariafilterDOMProps.filterDOMProps)(props.toast.content),
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($e4abcc5792370d9e$exports))), 'spectrum-Toast', {
            ['spectrum-Toast--' + variant]: variant
        }, styleProps.className, (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($1e451ff201076fe2$exports))), 'spectrum-Toast', {
            'focus-ring': isFocusVisible
        }))
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9Vw3a$react))).createElement("div", {
        ...contentProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($1e451ff201076fe2$exports))), 'spectrum-Toast-contentWrapper')
    }, Icon && /*#__PURE__*/ (0, ($parcel$interopDefault($9Vw3a$react))).createElement(Icon, {
        "aria-label": iconLabel,
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($e4abcc5792370d9e$exports))), 'spectrum-Toast-typeIcon')
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($9Vw3a$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($e4abcc5792370d9e$exports))), 'spectrum-Toast-body'),
        role: "presentation"
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9Vw3a$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($e4abcc5792370d9e$exports))), 'spectrum-Toast-content'),
        role: "presentation",
        ...titleProps
    }, children), actionLabel && /*#__PURE__*/ (0, ($parcel$interopDefault($9Vw3a$react))).createElement((0, $92256f4fe9ec9f59$exports.Button), {
        onPress: handleAction,
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($e4abcc5792370d9e$exports))), 'spectrum-Button'),
        variant: "secondary",
        staticColor: "white",
        "data-testid": "rsp-Toast-secondaryButton"
    }, actionLabel))), /*#__PURE__*/ (0, ($parcel$interopDefault($9Vw3a$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($e4abcc5792370d9e$exports))), 'spectrum-Toast-buttons')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9Vw3a$react))).createElement((0, $0fc8553a4214494f$exports.ClearButton), {
        ...closeButtonProps,
        variant: "overBackground",
        "data-testid": "rsp-Toast-closeButton"
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9Vw3a$react))).createElement((0, ($parcel$interopDefault($9Vw3a$spectrumiconsuiCrossMedium))), null))));
});


//# sourceMappingURL=Toast.cjs.map
