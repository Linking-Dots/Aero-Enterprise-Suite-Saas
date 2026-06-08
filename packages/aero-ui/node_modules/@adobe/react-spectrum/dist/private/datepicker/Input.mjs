import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "./styles.css";
import $ixFA2$styles_cssmjs from "./styles_css.mjs";
import "../textfield_vars.css";
import $ixFA2$textfield_vars_cssmjs from "../textfield_vars_css.mjs";
import $ixFA2$spectrumiconsuiAlertMedium from "@spectrum-icons/ui/AlertMedium";
import $ixFA2$spectrumiconsuiCheckmarkMedium from "@spectrum-icons/ui/CheckmarkMedium";
import {mergeProps as $ixFA2$mergeProps} from "react-aria/mergeProps";
import {mergeRefs as $ixFA2$mergeRefs} from "react-aria/mergeRefs";
import $ixFA2$react, {useRef as $ixFA2$useRef, useCallback as $ixFA2$useCallback} from "react";
import {useEvent as $ixFA2$useEvent} from "react-aria/private/utils/useEvent";
import {useFocusRing as $ixFA2$useFocusRing} from "react-aria/useFocusRing";
import {useLayoutEffect as $ixFA2$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useResizeObserver as $ixFA2$useResizeObserver} from "react-aria/private/utils/useResizeObserver";
import {useValueEffect as $ixFA2$useValueEffect} from "react-aria/private/utils/useValueEffect";


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












const $51cb122633c52627$export$f5b8910cec6cf069 = /*#__PURE__*/ (0, $ixFA2$react).forwardRef(function Input(props, ref) {
    let inputRef = (0, $ixFA2$useRef)(null);
    let { isDisabled: isDisabled, isQuiet: isQuiet, inputClassName: inputClassName, validationState: validationState, children: children, fieldProps: fieldProps, className: className, style: style, disableFocusRing: disableFocusRing } = props;
    // Reserve padding for the error icon when the width of the input is unconstrained.
    // When constrained, don't reserve space because adding it only when invalid will
    // not cause a layout shift.
    let [reservePadding, setReservePadding] = (0, $ixFA2$useValueEffect)(false);
    let onResize = (0, $ixFA2$useCallback)(()=>setReservePadding(function*(reservePadding) {
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
    (0, $ixFA2$useLayoutEffect)(onResize, [
        onResize
    ]);
    (0, $ixFA2$useResizeObserver)({
        ref: inputRef,
        onResize: onResize
    });
    // We also need to listen for resize events of the window so we can detect
    // when there is enough space for the padding to be re-added. Ideally we'd
    // use a resize observer on a parent element, but it's hard to know _what_
    // parent element.
    (0, $ixFA2$useEvent)((0, $ixFA2$useRef)(typeof window !== 'undefined' ? window : null), 'resize', onResize);
    let { focusProps: focusProps, isFocusVisible: isFocusVisible, isFocused: isFocused } = (0, $ixFA2$useFocusRing)({
        isTextInput: true,
        within: true
    });
    let isInvalid = validationState === 'invalid' && !isDisabled;
    let textfieldClass = (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ixFA2$textfield_vars_cssmjs))), 'spectrum-Textfield', {
        'spectrum-Textfield--invalid': isInvalid,
        'spectrum-Textfield--valid': validationState === 'valid' && !isDisabled,
        'spectrum-Textfield--quiet': isQuiet,
        'focus-ring': isFocusVisible && !disableFocusRing
    }, (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ixFA2$styles_cssmjs))), 'react-spectrum-Datepicker-field'), className);
    let inputClass = (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ixFA2$textfield_vars_cssmjs))), 'spectrum-Textfield-input', {
        'is-disabled': isDisabled,
        'is-focused': isFocused
    }, (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ixFA2$styles_cssmjs))), 'react-spectrum-DateField-Input'), reservePadding && (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ixFA2$styles_cssmjs))), 'react-spectrum-Datepicker-input'), inputClassName);
    let iconClass = (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ixFA2$textfield_vars_cssmjs))), 'spectrum-Textfield-validationIcon');
    let validationIcon = null;
    if (validationState === 'invalid' && !isDisabled) validationIcon = /*#__PURE__*/ (0, $ixFA2$react).createElement((0, $ixFA2$spectrumiconsuiAlertMedium), {
        "data-testid": "invalid-icon",
        UNSAFE_className: iconClass
    });
    else if (validationState === 'valid' && !isDisabled) validationIcon = /*#__PURE__*/ (0, $ixFA2$react).createElement((0, $ixFA2$spectrumiconsuiCheckmarkMedium), {
        "data-testid": "valid-icon",
        UNSAFE_className: iconClass
    });
    return /*#__PURE__*/ (0, $ixFA2$react).createElement("div", {
        role: "presentation",
        ...(0, $ixFA2$mergeProps)(fieldProps, focusProps),
        className: textfieldClass,
        style: style
    }, /*#__PURE__*/ (0, $ixFA2$react).createElement("div", {
        role: "presentation",
        className: inputClass
    }, /*#__PURE__*/ (0, $ixFA2$react).createElement("div", {
        role: "presentation",
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ixFA2$styles_cssmjs))), 'react-spectrum-Datepicker-inputContents'),
        ref: (0, $ixFA2$mergeRefs)(ref, inputRef)
    }, /*#__PURE__*/ (0, $ixFA2$react).createElement("div", {
        role: "presentation",
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ixFA2$styles_cssmjs))), 'react-spectrum-Datepicker-inputSized'),
        style: {
            minWidth: props.minWidth
        }
    }, children))), validationIcon);
});


export {$51cb122633c52627$export$f5b8910cec6cf069 as Input};
//# sourceMappingURL=Input.mjs.map
