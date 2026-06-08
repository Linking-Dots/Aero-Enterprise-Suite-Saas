import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, removeDataAttributes as $7230ffa83bc0c2cf$export$ef03459518577ad4, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {FieldErrorContext as $1f3c3b1a70cec653$export$ff05c3ac10437e03} from "./FieldError.mjs";
import {FormContext as $cdaed739b1139372$export$c24727297075ec6a} from "./Form.mjs";
import {TextContext as $efe09c6d1c304b50$export$9afb8bc826b033ea} from "./Text.mjs";
import {useSwitch as $kf3DM$useSwitch} from "react-aria/useSwitch";
import {filterDOMProps as $kf3DM$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $kf3DM$mergeProps} from "react-aria/mergeProps";
import {mergeRefs as $kf3DM$mergeRefs} from "react-aria/mergeRefs";
import $kf3DM$react, {createContext as $kf3DM$createContext, forwardRef as $kf3DM$forwardRef, useContext as $kf3DM$useContext} from "react";
import {useToggleState as $kf3DM$useToggleState} from "react-stately/useToggleState";
import {useFocusRing as $kf3DM$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $kf3DM$useHover} from "react-aria/useHover";
import {useObjectRef as $kf3DM$useObjectRef} from "react-aria/useObjectRef";
import {VisuallyHidden as $kf3DM$VisuallyHidden} from "react-aria/VisuallyHidden";

/*
 * Copyright 2022 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 













const $24585aa46d6ffdff$export$8699e3b644d5a28a = /*#__PURE__*/ (0, $kf3DM$createContext)(null);
const $24585aa46d6ffdff$export$3e1405298f0cafda = /*#__PURE__*/ (0, $kf3DM$createContext)(null);
const $24585aa46d6ffdff$export$5cc1518e1ec171c = /*#__PURE__*/ (0, $kf3DM$createContext)(null);
const $24585aa46d6ffdff$export$b5d5cf8927ab7262 = /*#__PURE__*/ (0, $kf3DM$forwardRef)(function Switch(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(otherProps, ref, $24585aa46d6ffdff$export$8699e3b644d5a28a);
    let inputRef = (0, $kf3DM$useObjectRef)((0, $kf3DM$mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null));
    let state = (0, $kf3DM$useToggleState)(props);
    let aria = (0, $kf3DM$useSwitch)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        // ReactNode type doesn't allow function children.
        children: typeof props.children === 'function' ? true : props.children
    }, state, inputRef);
    return /*#__PURE__*/ (0, $kf3DM$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $24585aa46d6ffdff$export$5cc1518e1ec171c,
                state
            ],
            [
                $24585aa46d6ffdff$var$InternalSwitchContext,
                {
                    ...aria,
                    inputRef: inputRef,
                    defaultClassName: 'react-aria-Switch'
                }
            ]
        ]
    }, /*#__PURE__*/ (0, $kf3DM$react).createElement($24585aa46d6ffdff$export$72111f742dee7cb8, {
        ...props,
        ref: ref
    }));
});
const $24585aa46d6ffdff$var$InternalSwitchContext = /*#__PURE__*/ (0, $kf3DM$createContext)(null);
const $24585aa46d6ffdff$export$208c2e617baf9fc3 = /*#__PURE__*/ (0, $kf3DM$forwardRef)(function Switch(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(otherProps, ref, $24585aa46d6ffdff$export$3e1405298f0cafda);
    let { validationBehavior: formValidationBehavior } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)((0, $cdaed739b1139372$export$c24727297075ec6a)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let inputRef = (0, $kf3DM$useObjectRef)((0, $kf3DM$mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null));
    let state = (0, $kf3DM$useToggleState)(props);
    let aria = (0, $kf3DM$useSwitch)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        // ReactNode type doesn't allow function children.
        children: typeof props.children === 'function' ? true : props.children,
        validationBehavior: validationBehavior
    }, state, inputRef);
    let { descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isSelected: isSelected, isDisabled: isDisabled, isReadOnly: isReadOnly, isInvalid: isInvalid, validationDetails: validationDetails, validationErrors: validationErrors } = aria;
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-SwitchField',
        values: {
            isSelected: isSelected,
            isDisabled: isDisabled,
            isReadOnly: isReadOnly,
            isInvalid: isInvalid,
            isRequired: props.isRequired || false,
            state: state
        }
    });
    let DOMProps = (0, $kf3DM$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $kf3DM$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $kf3DM$mergeProps)(DOMProps, renderProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-selected": isSelected || undefined,
        "data-disabled": isDisabled || undefined,
        "data-readonly": isReadOnly || undefined,
        "data-invalid": isInvalid || undefined,
        "data-required": props.isRequired || undefined
    }, /*#__PURE__*/ (0, $kf3DM$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $24585aa46d6ffdff$export$5cc1518e1ec171c,
                state
            ],
            [
                $24585aa46d6ffdff$var$InternalSwitchContext,
                {
                    ...aria,
                    inputRef: inputRef,
                    defaultClassName: 'react-aria-SwitchButton',
                    isRequired: props.isRequired
                }
            ],
            [
                (0, $efe09c6d1c304b50$export$9afb8bc826b033ea),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $1f3c3b1a70cec653$export$ff05c3ac10437e03),
                {
                    isInvalid: isInvalid,
                    validationDetails: validationDetails,
                    validationErrors: validationErrors
                }
            ]
        ]
    }, renderProps.children));
});
const $24585aa46d6ffdff$export$72111f742dee7cb8 = /*#__PURE__*/ (0, $kf3DM$forwardRef)(function SwitchButton(props, ref) {
    let { labelProps: labelProps, inputProps: inputProps, isSelected: isSelected, isDisabled: isDisabled, isReadOnly: isReadOnly, isPressed: isPressed, isInvalid: isInvalid, inputRef: inputRef, defaultClassName: defaultClassName, isRequired: isRequired } = (0, $kf3DM$useContext)($24585aa46d6ffdff$var$InternalSwitchContext);
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $kf3DM$useFocusRing)();
    let isInteractionDisabled = isDisabled || isReadOnly;
    let state = (0, $kf3DM$useContext)($24585aa46d6ffdff$export$5cc1518e1ec171c);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $kf3DM$useHover)({
        ...props,
        isDisabled: isInteractionDisabled
    });
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: defaultClassName,
        values: {
            isSelected: isSelected,
            isPressed: isPressed,
            isHovered: isHovered,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: isDisabled,
            isReadOnly: isReadOnly,
            isInvalid: isInvalid,
            isRequired: isRequired || false,
            state: state
        }
    });
    let DOMProps = (0, $kf3DM$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $kf3DM$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).label, {
        ...(0, $kf3DM$mergeProps)(DOMProps, labelProps, hoverProps, renderProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-selected": isSelected || undefined,
        "data-pressed": isPressed || undefined,
        "data-hovered": isHovered || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": isDisabled || undefined,
        "data-readonly": isReadOnly || undefined,
        "data-invalid": isInvalid || undefined,
        "data-required": isRequired || undefined
    }, /*#__PURE__*/ (0, $kf3DM$react).createElement((0, $kf3DM$VisuallyHidden), {
        elementType: "span"
    }, /*#__PURE__*/ (0, $kf3DM$react).createElement("input", {
        ...(0, $kf3DM$mergeProps)(inputProps, focusProps),
        ref: inputRef
    })), renderProps.children);
});


export {$24585aa46d6ffdff$export$8699e3b644d5a28a as SwitchContext, $24585aa46d6ffdff$export$3e1405298f0cafda as SwitchFieldContext, $24585aa46d6ffdff$export$5cc1518e1ec171c as ToggleStateContext, $24585aa46d6ffdff$export$b5d5cf8927ab7262 as Switch, $24585aa46d6ffdff$export$72111f742dee7cb8 as SwitchButton, $24585aa46d6ffdff$export$208c2e617baf9fc3 as SwitchField};
//# sourceMappingURL=Switch.mjs.map
