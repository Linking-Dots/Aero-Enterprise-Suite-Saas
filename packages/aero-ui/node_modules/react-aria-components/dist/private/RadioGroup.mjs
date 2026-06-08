import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, removeDataAttributes as $7230ffa83bc0c2cf$export$ef03459518577ad4, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlot as $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {FieldErrorContext as $1f3c3b1a70cec653$export$ff05c3ac10437e03} from "./FieldError.mjs";
import {FormContext as $cdaed739b1139372$export$c24727297075ec6a} from "./Form.mjs";
import {LabelContext as $43a3b93638fe5db9$export$75b6ee27786ba447} from "./Label.mjs";
import {SelectionIndicatorContext as $91fe5e721c7f36c1$export$c9549807523555e0} from "./SelectionIndicator.mjs";
import {SharedElementTransition as $792f28e438b9ad5f$export$758399f318e6385a} from "./SharedElementTransition.mjs";
import {TextContext as $efe09c6d1c304b50$export$9afb8bc826b033ea} from "./Text.mjs";
import {useRadioGroup as $64B8K$useRadioGroup, useRadio as $64B8K$useRadio} from "react-aria/useRadioGroup";
import {filterDOMProps as $64B8K$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $64B8K$mergeProps} from "react-aria/mergeProps";
import {mergeRefs as $64B8K$mergeRefs} from "react-aria/mergeRefs";
import {useRadioGroupState as $64B8K$useRadioGroupState} from "react-stately/useRadioGroupState";
import $64B8K$react, {createContext as $64B8K$createContext, forwardRef as $64B8K$forwardRef, useMemo as $64B8K$useMemo, useContext as $64B8K$useContext} from "react";
import {useFocusRing as $64B8K$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $64B8K$useHover} from "react-aria/useHover";
import {useObjectRef as $64B8K$useObjectRef} from "react-aria/useObjectRef";
import {VisuallyHidden as $64B8K$VisuallyHidden} from "react-aria/VisuallyHidden";

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
















const $fe21c36db05242bb$export$a79eda4ff50e30b6 = /*#__PURE__*/ (0, $64B8K$createContext)(null);
const $fe21c36db05242bb$export$b118023277d4a5c3 = /*#__PURE__*/ (0, $64B8K$createContext)(null);
const $fe21c36db05242bb$export$29c6814b341e632b = /*#__PURE__*/ (0, $64B8K$createContext)(null);
const $fe21c36db05242bb$export$29d84393af70866c = /*#__PURE__*/ (0, $64B8K$createContext)(null);
const $fe21c36db05242bb$export$a98f0dcb43a68a25 = /*#__PURE__*/ (0, $64B8K$forwardRef)(function RadioGroup(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $fe21c36db05242bb$export$a79eda4ff50e30b6);
    let { validationBehavior: formValidationBehavior } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)((0, $cdaed739b1139372$export$c24727297075ec6a)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let state = (0, $64B8K$useRadioGroupState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { radioGroupProps: radioGroupProps, labelProps: labelProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $64B8K$useRadioGroup)({
        ...props,
        label: label,
        validationBehavior: validationBehavior
    }, state);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            orientation: props.orientation || 'vertical',
            isDisabled: state.isDisabled,
            isReadOnly: state.isReadOnly,
            isRequired: state.isRequired,
            isInvalid: state.isInvalid,
            state: state
        },
        defaultClassName: 'react-aria-RadioGroup'
    });
    let DOMProps = (0, $64B8K$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $64B8K$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $64B8K$mergeProps)(DOMProps, renderProps, radioGroupProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-orientation": props.orientation || 'vertical',
        "data-invalid": state.isInvalid || undefined,
        "data-disabled": state.isDisabled || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-required": state.isRequired || undefined
    }, /*#__PURE__*/ (0, $64B8K$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $fe21c36db05242bb$export$29d84393af70866c,
                state
            ],
            [
                (0, $43a3b93638fe5db9$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef,
                    elementType: 'span'
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
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, $64B8K$react).createElement((0, $792f28e438b9ad5f$export$758399f318e6385a), null, renderProps.children)));
});
const $fe21c36db05242bb$export$d7b12c4107be0d61 = /*#__PURE__*/ (0, $64B8K$forwardRef)(function Radio(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(otherProps, ref, $fe21c36db05242bb$export$b118023277d4a5c3);
    let state = (0, $64B8K$react).useContext($fe21c36db05242bb$export$29d84393af70866c);
    let inputRef = (0, $64B8K$useObjectRef)((0, $64B8K$useMemo)(()=>(0, $64B8K$mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null), [
        userProvidedInputRef,
        props.inputRef
    ]));
    let aria = (0, $64B8K$useRadio)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        // ReactNode type doesn't allow function children.
        children: typeof props.children === 'function' ? true : props.children
    }, state, inputRef);
    return /*#__PURE__*/ (0, $64B8K$react).createElement($fe21c36db05242bb$var$InternalRadioContext.Provider, {
        value: {
            ...aria,
            inputRef: inputRef,
            defaultClassName: 'react-aria-Radio'
        }
    }, /*#__PURE__*/ (0, $64B8K$react).createElement($fe21c36db05242bb$export$f4422ae58352e179, {
        ...props,
        ref: ref
    }));
});
const $fe21c36db05242bb$var$InternalRadioContext = /*#__PURE__*/ (0, $64B8K$createContext)(null);
const $fe21c36db05242bb$export$4aaf0c609b3e241e = /*#__PURE__*/ (0, $64B8K$forwardRef)(function RadioField(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(otherProps, ref, $fe21c36db05242bb$export$29c6814b341e632b);
    let state = (0, $64B8K$react).useContext($fe21c36db05242bb$export$29d84393af70866c);
    let inputRef = (0, $64B8K$useObjectRef)((0, $64B8K$useMemo)(()=>(0, $64B8K$mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null), [
        userProvidedInputRef,
        props.inputRef
    ]));
    let aria = (0, $64B8K$useRadio)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        // ReactNode type doesn't allow function children.
        children: typeof props.children === 'function' ? true : props.children
    }, state, inputRef);
    let { descriptionProps: descriptionProps, isSelected: isSelected, isDisabled: isDisabled } = aria;
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-RadioField',
        values: {
            isSelected: isSelected,
            isDisabled: isDisabled,
            isReadOnly: state.isReadOnly,
            isInvalid: state.isInvalid,
            isRequired: state.isRequired
        }
    });
    let DOMProps = (0, $64B8K$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $64B8K$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $64B8K$mergeProps)(DOMProps, renderProps),
        ref: ref,
        "data-selected": isSelected || undefined,
        "data-disabled": isDisabled || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-required": state.isRequired || undefined
    }, /*#__PURE__*/ (0, $64B8K$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                (0, $91fe5e721c7f36c1$export$c9549807523555e0),
                {
                    isSelected: isSelected
                }
            ],
            [
                $fe21c36db05242bb$var$InternalRadioContext,
                {
                    ...aria,
                    inputRef: inputRef,
                    defaultClassName: 'react-aria-RadioButton'
                }
            ],
            [
                (0, $efe09c6d1c304b50$export$9afb8bc826b033ea),
                {
                    slots: {
                        description: descriptionProps
                    }
                }
            ]
        ]
    }, renderProps.children));
});
const $fe21c36db05242bb$export$f4422ae58352e179 = /*#__PURE__*/ (0, $64B8K$forwardRef)(function RadioButton(props, ref) {
    let { labelProps: labelProps, inputProps: inputProps, isSelected: isSelected, isDisabled: isDisabled, isPressed: isPressed, defaultClassName: defaultClassName, inputRef: inputRef } = (0, $64B8K$useContext)($fe21c36db05242bb$var$InternalRadioContext);
    let state = (0, $64B8K$react).useContext($fe21c36db05242bb$export$29d84393af70866c);
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $64B8K$useFocusRing)();
    let interactionDisabled = isDisabled || state.isReadOnly;
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $64B8K$useHover)({
        ...props,
        isDisabled: interactionDisabled
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
            isReadOnly: state.isReadOnly,
            isInvalid: state.isInvalid,
            isRequired: state.isRequired
        }
    });
    let DOMProps = (0, $64B8K$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $64B8K$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).label, {
        ...(0, $64B8K$mergeProps)(DOMProps, labelProps, hoverProps, renderProps),
        ref: ref,
        "data-selected": isSelected || undefined,
        "data-pressed": isPressed || undefined,
        "data-hovered": isHovered || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": isDisabled || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-required": state.isRequired || undefined
    }, /*#__PURE__*/ (0, $64B8K$react).createElement((0, $64B8K$VisuallyHidden), {
        elementType: "span"
    }, /*#__PURE__*/ (0, $64B8K$react).createElement("input", {
        ...(0, $64B8K$mergeProps)(inputProps, focusProps),
        ref: inputRef
    })), renderProps.children);
});


export {$fe21c36db05242bb$export$a79eda4ff50e30b6 as RadioGroupContext, $fe21c36db05242bb$export$b118023277d4a5c3 as RadioContext, $fe21c36db05242bb$export$29c6814b341e632b as RadioFieldContext, $fe21c36db05242bb$export$29d84393af70866c as RadioGroupStateContext, $fe21c36db05242bb$export$a98f0dcb43a68a25 as RadioGroup, $fe21c36db05242bb$export$d7b12c4107be0d61 as Radio, $fe21c36db05242bb$export$f4422ae58352e179 as RadioButton, $fe21c36db05242bb$export$4aaf0c609b3e241e as RadioField};
//# sourceMappingURL=RadioGroup.mjs.map
