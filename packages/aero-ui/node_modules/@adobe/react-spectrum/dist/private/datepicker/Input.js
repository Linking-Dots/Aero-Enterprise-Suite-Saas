import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "./styles.css";
import $29Psu$styles_cssmjs from "./styles_css.mjs";
import "../textfield_vars.css";
import $29Psu$textfield_vars_cssmjs from "../textfield_vars_css.mjs";
import $29Psu$spectrumiconsuiAlertMedium from "@spectrum-icons/ui/AlertMedium";
import $29Psu$spectrumiconsuiCheckmarkMedium from "@spectrum-icons/ui/CheckmarkMedium";
import {mergeProps as $29Psu$mergeProps} from "react-aria/mergeProps";
import {mergeRefs as $29Psu$mergeRefs} from "react-aria/mergeRefs";
import $29Psu$react, {useRef as $29Psu$useRef, useCallback as $29Psu$useCallback} from "react";
import {useEvent as $29Psu$useEvent} from "react-aria/private/utils/useEvent";
import {useFocusRing as $29Psu$useFocusRing} from "react-aria/useFocusRing";
import {useLayoutEffect as $29Psu$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useResizeObserver as $29Psu$useResizeObserver} from "react-aria/private/utils/useResizeObserver";
import {useValueEffect as $29Psu$useValueEffect} from "react-aria/private/utils/useValueEffect";


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












const $f0b9f6972621ffb5$export$f5b8910cec6cf069 = /*#__PURE__*/ (0, $29Psu$react).forwardRef(function Input(props, ref) {
    let inputRef = (0, $29Psu$useRef)(null);
    let { isDisabled: isDisabled, isQuiet: isQuiet, inputClassName: inputClassName, validationState: validationState, children: children, fieldProps: fieldProps, className: className, style: style, disableFocusRing: disableFocusRing } = props;
    // Reserve padding for the error icon when the width of the input is unconstrained.
    // When constrained, don't reserve space because adding it only when invalid will
    // not cause a layout shift.
    let [reservePadding, setReservePadding] = (0, $29Psu$useValueEffect)(false);
    let onResize = (0, $29Psu$useCallback)(()=>setReservePadding(function*(reservePadding) {
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
    (0, $29Psu$useLayoutEffect)(onResize, [
        onResize
    ]);
    (0, $29Psu$useResizeObserver)({
        ref: inputRef,
        onResize: onResize
    });
    // We also need to listen for resize events of the window so we can detect
    // when there is enough space for the padding to be re-added. Ideally we'd
    // use a resize observer on a parent element, but it's hard to know _what_
    // parent element.
    (0, $29Psu$useEvent)((0, $29Psu$useRef)(typeof window !== 'undefined' ? window : null), 'resize', onResize);
    let { focusProps: focusProps, isFocusVisible: isFocusVisible, isFocused: isFocused } = (0, $29Psu$useFocusRing)({
        isTextInput: true,
        within: true
    });
    let isInvalid = validationState === 'invalid' && !isDisabled;
    let textfieldClass = (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($29Psu$textfield_vars_cssmjs))), 'spectrum-Textfield', {
        'spectrum-Textfield--invalid': isInvalid,
        'spectrum-Textfield--valid': validationState === 'valid' && !isDisabled,
        'spectrum-Textfield--quiet': isQuiet,
        'focus-ring': isFocusVisible && !disableFocusRing
    }, (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($29Psu$styles_cssmjs))), 'react-spectrum-Datepicker-field'), className);
    let inputClass = (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($29Psu$textfield_vars_cssmjs))), 'spectrum-Textfield-input', {
        'is-disabled': isDisabled,
        'is-focused': isFocused
    }, (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($29Psu$styles_cssmjs))), 'react-spectrum-DateField-Input'), reservePadding && (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($29Psu$styles_cssmjs))), 'react-spectrum-Datepicker-input'), inputClassName);
    let iconClass = (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($29Psu$textfield_vars_cssmjs))), 'spectrum-Textfield-validationIcon');
    let validationIcon = null;
    if (validationState === 'invalid' && !isDisabled) validationIcon = /*#__PURE__*/ (0, $29Psu$react).createElement((0, $29Psu$spectrumiconsuiAlertMedium), {
        "data-testid": "invalid-icon",
        UNSAFE_className: iconClass
    });
    else if (validationState === 'valid' && !isDisabled) validationIcon = /*#__PURE__*/ (0, $29Psu$react).createElement((0, $29Psu$spectrumiconsuiCheckmarkMedium), {
        "data-testid": "valid-icon",
        UNSAFE_className: iconClass
    });
    return /*#__PURE__*/ (0, $29Psu$react).createElement("div", {
        role: "presentation",
        ...(0, $29Psu$mergeProps)(fieldProps, focusProps),
        className: textfieldClass,
        style: style
    }, /*#__PURE__*/ (0, $29Psu$react).createElement("div", {
        role: "presentation",
        className: inputClass
    }, /*#__PURE__*/ (0, $29Psu$react).createElement("div", {
        role: "presentation",
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($29Psu$styles_cssmjs))), 'react-spectrum-Datepicker-inputContents'),
        ref: (0, $29Psu$mergeRefs)(ref, inputRef)
    }, /*#__PURE__*/ (0, $29Psu$react).createElement("div", {
        role: "presentation",
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($29Psu$styles_cssmjs))), 'react-spectrum-Datepicker-inputSized'),
        style: {
            minWidth: props.minWidth
        }
    }, children))), validationIcon);
});


export {$f0b9f6972621ffb5$export$f5b8910cec6cf069 as Input};
//# sourceMappingURL=Input.js.map
