import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, removeDataAttributes as $b7b7a92703138c9b$export$ef03459518577ad4, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3, useSlottedContext as $b7b7a92703138c9b$export$fabf2dc03a41866e} from "./utils.js";
import {FieldErrorContext as $6567560e1d9cc847$export$ff05c3ac10437e03} from "./FieldError.js";
import {FormContext as $c7332d4a2d191cd2$export$c24727297075ec6a} from "./Form.js";
import {TextContext as $20d769b1e2b13352$export$9afb8bc826b033ea} from "./Text.js";
import {useSwitch as $aLaER$useSwitch} from "react-aria/useSwitch";
import {filterDOMProps as $aLaER$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $aLaER$mergeProps} from "react-aria/mergeProps";
import {mergeRefs as $aLaER$mergeRefs} from "react-aria/mergeRefs";
import $aLaER$react, {createContext as $aLaER$createContext, forwardRef as $aLaER$forwardRef, useContext as $aLaER$useContext} from "react";
import {useToggleState as $aLaER$useToggleState} from "react-stately/useToggleState";
import {useFocusRing as $aLaER$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $aLaER$useHover} from "react-aria/useHover";
import {useObjectRef as $aLaER$useObjectRef} from "react-aria/useObjectRef";
import {VisuallyHidden as $aLaER$VisuallyHidden} from "react-aria/VisuallyHidden";

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













const $7a050e53d1672c98$export$8699e3b644d5a28a = /*#__PURE__*/ (0, $aLaER$createContext)(null);
const $7a050e53d1672c98$export$3e1405298f0cafda = /*#__PURE__*/ (0, $aLaER$createContext)(null);
const $7a050e53d1672c98$export$5cc1518e1ec171c = /*#__PURE__*/ (0, $aLaER$createContext)(null);
const $7a050e53d1672c98$export$b5d5cf8927ab7262 = /*#__PURE__*/ (0, $aLaER$forwardRef)(function Switch(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(otherProps, ref, $7a050e53d1672c98$export$8699e3b644d5a28a);
    let inputRef = (0, $aLaER$useObjectRef)((0, $aLaER$mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null));
    let state = (0, $aLaER$useToggleState)(props);
    let aria = (0, $aLaER$useSwitch)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        // ReactNode type doesn't allow function children.
        children: typeof props.children === 'function' ? true : props.children
    }, state, inputRef);
    return /*#__PURE__*/ (0, $aLaER$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $7a050e53d1672c98$export$5cc1518e1ec171c,
                state
            ],
            [
                $7a050e53d1672c98$var$InternalSwitchContext,
                {
                    ...aria,
                    inputRef: inputRef,
                    defaultClassName: 'react-aria-Switch'
                }
            ]
        ]
    }, /*#__PURE__*/ (0, $aLaER$react).createElement($7a050e53d1672c98$export$72111f742dee7cb8, {
        ...props,
        ref: ref
    }));
});
const $7a050e53d1672c98$var$InternalSwitchContext = /*#__PURE__*/ (0, $aLaER$createContext)(null);
const $7a050e53d1672c98$export$208c2e617baf9fc3 = /*#__PURE__*/ (0, $aLaER$forwardRef)(function Switch(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(otherProps, ref, $7a050e53d1672c98$export$3e1405298f0cafda);
    let { validationBehavior: formValidationBehavior } = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)((0, $c7332d4a2d191cd2$export$c24727297075ec6a)) || {};
    var _props_validationBehavior, _ref;
    let validationBehavior = (_ref = (_props_validationBehavior = props.validationBehavior) !== null && _props_validationBehavior !== void 0 ? _props_validationBehavior : formValidationBehavior) !== null && _ref !== void 0 ? _ref : 'native';
    let inputRef = (0, $aLaER$useObjectRef)((0, $aLaER$mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null));
    let state = (0, $aLaER$useToggleState)(props);
    let aria = (0, $aLaER$useSwitch)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        // ReactNode type doesn't allow function children.
        children: typeof props.children === 'function' ? true : props.children,
        validationBehavior: validationBehavior
    }, state, inputRef);
    let { descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isSelected: isSelected, isDisabled: isDisabled, isReadOnly: isReadOnly, isInvalid: isInvalid, validationDetails: validationDetails, validationErrors: validationErrors } = aria;
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $aLaER$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $aLaER$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $aLaER$mergeProps)(DOMProps, renderProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-selected": isSelected || undefined,
        "data-disabled": isDisabled || undefined,
        "data-readonly": isReadOnly || undefined,
        "data-invalid": isInvalid || undefined,
        "data-required": props.isRequired || undefined
    }, /*#__PURE__*/ (0, $aLaER$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $7a050e53d1672c98$export$5cc1518e1ec171c,
                state
            ],
            [
                $7a050e53d1672c98$var$InternalSwitchContext,
                {
                    ...aria,
                    inputRef: inputRef,
                    defaultClassName: 'react-aria-SwitchButton',
                    isRequired: props.isRequired
                }
            ],
            [
                (0, $20d769b1e2b13352$export$9afb8bc826b033ea),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $6567560e1d9cc847$export$ff05c3ac10437e03),
                {
                    isInvalid: isInvalid,
                    validationDetails: validationDetails,
                    validationErrors: validationErrors
                }
            ]
        ]
    }, renderProps.children));
});
const $7a050e53d1672c98$export$72111f742dee7cb8 = /*#__PURE__*/ (0, $aLaER$forwardRef)(function SwitchButton(props, ref) {
    let { labelProps: labelProps, inputProps: inputProps, isSelected: isSelected, isDisabled: isDisabled, isReadOnly: isReadOnly, isPressed: isPressed, isInvalid: isInvalid, inputRef: inputRef, defaultClassName: defaultClassName, isRequired: isRequired } = (0, $aLaER$useContext)($7a050e53d1672c98$var$InternalSwitchContext);
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $aLaER$useFocusRing)();
    let isInteractionDisabled = isDisabled || isReadOnly;
    let state = (0, $aLaER$useContext)($7a050e53d1672c98$export$5cc1518e1ec171c);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $aLaER$useHover)({
        ...props,
        isDisabled: isInteractionDisabled
    });
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $aLaER$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $aLaER$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).label, {
        ...(0, $aLaER$mergeProps)(DOMProps, labelProps, hoverProps, renderProps),
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
    }, /*#__PURE__*/ (0, $aLaER$react).createElement((0, $aLaER$VisuallyHidden), {
        elementType: "span"
    }, /*#__PURE__*/ (0, $aLaER$react).createElement("input", {
        ...(0, $aLaER$mergeProps)(inputProps, focusProps),
        ref: inputRef
    })), renderProps.children);
});


export {$7a050e53d1672c98$export$8699e3b644d5a28a as SwitchContext, $7a050e53d1672c98$export$3e1405298f0cafda as SwitchFieldContext, $7a050e53d1672c98$export$5cc1518e1ec171c as ToggleStateContext, $7a050e53d1672c98$export$b5d5cf8927ab7262 as Switch, $7a050e53d1672c98$export$72111f742dee7cb8 as SwitchButton, $7a050e53d1672c98$export$208c2e617baf9fc3 as SwitchField};
//# sourceMappingURL=Switch.js.map
