var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("./styles.css");
var $25dd6e69bdd309d3$exports = require("./styles_css.cjs");
require("../textfield_vars.css");
var $5d389146adc85829$exports = require("../textfield_vars_css.cjs");
var $5c7VD$spectrumiconsuiAlertMedium = require("@spectrum-icons/ui/AlertMedium");
var $5c7VD$spectrumiconsuiCheckmarkMedium = require("@spectrum-icons/ui/CheckmarkMedium");
var $5c7VD$reactariamergeProps = require("react-aria/mergeProps");
var $5c7VD$reactariamergeRefs = require("react-aria/mergeRefs");
var $5c7VD$react = require("react");
var $5c7VD$reactariaprivateutilsuseEvent = require("react-aria/private/utils/useEvent");
var $5c7VD$reactariauseFocusRing = require("react-aria/useFocusRing");
var $5c7VD$reactariaprivateutilsuseLayoutEffect = require("react-aria/private/utils/useLayoutEffect");
var $5c7VD$reactariaprivateutilsuseResizeObserver = require("react-aria/private/utils/useResizeObserver");
var $5c7VD$reactariaprivateutilsuseValueEffect = require("react-aria/private/utils/useValueEffect");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Input", function () { return $5d83a0dbed853d9d$export$f5b8910cec6cf069; });
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












const $5d83a0dbed853d9d$export$f5b8910cec6cf069 = /*#__PURE__*/ (0, ($parcel$interopDefault($5c7VD$react))).forwardRef(function Input(props, ref) {
    let inputRef = (0, $5c7VD$react.useRef)(null);
    let { isDisabled: isDisabled, isQuiet: isQuiet, inputClassName: inputClassName, validationState: validationState, children: children, fieldProps: fieldProps, className: className, style: style, disableFocusRing: disableFocusRing } = props;
    // Reserve padding for the error icon when the width of the input is unconstrained.
    // When constrained, don't reserve space because adding it only when invalid will
    // not cause a layout shift.
    let [reservePadding, setReservePadding] = (0, $5c7VD$reactariaprivateutilsuseValueEffect.useValueEffect)(false);
    let onResize = (0, $5c7VD$react.useCallback)(()=>setReservePadding(function*(reservePadding) {
            if (inputRef.current && inputRef.current.parentElement) {
                if (reservePadding) // Try to collapse padding if the content is clipped.
                {
                    if (inputRef.current.scrollWidth > inputRef.current.offsetWidth) {
                        let width = inputRef.current.parentElement.offsetWidth;
                        yield false;
                        // If removing padding causes a layout shift, add it back.
                        if (inputRef.current.parentElement.offsetWidth !== width) yield true;
                    }
                } else // Try to add padding if the content is not clipped.
                if (inputRef.current.offsetWidth >= inputRef.current.scrollWidth) {
                    let width = inputRef.current.parentElement.offsetWidth;
                    yield true;
                    // If adding padding does not change the width (i.e. width is constrained), remove it again.
                    if (inputRef.current.parentElement.offsetWidth === width) yield false;
                }
            }
        }), [
        inputRef,
        setReservePadding
    ]);
    (0, $5c7VD$reactariaprivateutilsuseLayoutEffect.useLayoutEffect)(onResize, [
        onResize
    ]);
    (0, $5c7VD$reactariaprivateutilsuseResizeObserver.useResizeObserver)({
        ref: inputRef,
        onResize: onResize
    });
    // We also need to listen for resize events of the window so we can detect
    // when there is enough space for the padding to be re-added. Ideally we'd
    // use a resize observer on a parent element, but it's hard to know _what_
    // parent element.
    (0, $5c7VD$reactariaprivateutilsuseEvent.useEvent)((0, $5c7VD$react.useRef)(typeof window !== 'undefined' ? window : null), 'resize', onResize);
    let { focusProps: focusProps, isFocusVisible: isFocusVisible, isFocused: isFocused } = (0, $5c7VD$reactariauseFocusRing.useFocusRing)({
        isTextInput: true,
        within: true
    });
    let isInvalid = validationState === 'invalid' && !isDisabled;
    let textfieldClass = (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($5d389146adc85829$exports))), 'spectrum-Textfield', {
        'spectrum-Textfield--invalid': isInvalid,
        'spectrum-Textfield--valid': validationState === 'valid' && !isDisabled,
        'spectrum-Textfield--quiet': isQuiet,
        'focus-ring': isFocusVisible && !disableFocusRing
    }, (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($25dd6e69bdd309d3$exports))), 'react-spectrum-Datepicker-field'), className);
    let inputClass = (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($5d389146adc85829$exports))), 'spectrum-Textfield-input', {
        'is-disabled': isDisabled,
        'is-focused': isFocused
    }, (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($25dd6e69bdd309d3$exports))), 'react-spectrum-DateField-Input'), reservePadding && (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($25dd6e69bdd309d3$exports))), 'react-spectrum-Datepicker-input'), inputClassName);
    let iconClass = (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($5d389146adc85829$exports))), 'spectrum-Textfield-validationIcon');
    let validationIcon = null;
    if (validationState === 'invalid' && !isDisabled) validationIcon = /*#__PURE__*/ (0, ($parcel$interopDefault($5c7VD$react))).createElement((0, ($parcel$interopDefault($5c7VD$spectrumiconsuiAlertMedium))), {
        "data-testid": "invalid-icon",
        UNSAFE_className: iconClass
    });
    else if (validationState === 'valid' && !isDisabled) validationIcon = /*#__PURE__*/ (0, ($parcel$interopDefault($5c7VD$react))).createElement((0, ($parcel$interopDefault($5c7VD$spectrumiconsuiCheckmarkMedium))), {
        "data-testid": "valid-icon",
        UNSAFE_className: iconClass
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5c7VD$react))).createElement("div", {
        role: "presentation",
        ...(0, $5c7VD$reactariamergeProps.mergeProps)(fieldProps, focusProps),
        className: textfieldClass,
        style: style
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($5c7VD$react))).createElement("div", {
        role: "presentation",
        className: inputClass
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($5c7VD$react))).createElement("div", {
        role: "presentation",
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($25dd6e69bdd309d3$exports))), 'react-spectrum-Datepicker-inputContents'),
        ref: (0, $5c7VD$reactariamergeRefs.mergeRefs)(ref, inputRef)
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($5c7VD$react))).createElement("div", {
        role: "presentation",
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($25dd6e69bdd309d3$exports))), 'react-spectrum-Datepicker-inputSized'),
        style: {
            minWidth: props.minWidth
        }
    }, children))), validationIcon);
});


//# sourceMappingURL=Input.cjs.map
