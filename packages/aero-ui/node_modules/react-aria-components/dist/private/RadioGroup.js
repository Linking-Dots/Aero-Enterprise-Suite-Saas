import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, removeDataAttributes as $b7b7a92703138c9b$export$ef03459518577ad4, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3, useSlot as $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8, useSlottedContext as $b7b7a92703138c9b$export$fabf2dc03a41866e} from "./utils.js";
import {FieldErrorContext as $6567560e1d9cc847$export$ff05c3ac10437e03} from "./FieldError.js";
import {FormContext as $c7332d4a2d191cd2$export$c24727297075ec6a} from "./Form.js";
import {LabelContext as $3e4839e5b30e7b17$export$75b6ee27786ba447} from "./Label.js";
import {SelectionIndicatorContext as $0d6f83ad40839938$export$c9549807523555e0} from "./SelectionIndicator.js";
import {SharedElementTransition as $347bc273c4058e94$export$758399f318e6385a} from "./SharedElementTransition.js";
import {TextContext as $20d769b1e2b13352$export$9afb8bc826b033ea} from "./Text.js";
import {useRadioGroup as $kK41C$useRadioGroup, useRadio as $kK41C$useRadio} from "react-aria/useRadioGroup";
import {filterDOMProps as $kK41C$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $kK41C$mergeProps} from "react-aria/mergeProps";
import {mergeRefs as $kK41C$mergeRefs} from "react-aria/mergeRefs";
import {useRadioGroupState as $kK41C$useRadioGroupState} from "react-stately/useRadioGroupState";
import $kK41C$react, {createContext as $kK41C$createContext, forwardRef as $kK41C$forwardRef, useMemo as $kK41C$useMemo, useContext as $kK41C$useContext} from "react";
import {useFocusRing as $kK41C$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $kK41C$useHover} from "react-aria/useHover";
import {useObjectRef as $kK41C$useObjectRef} from "react-aria/useObjectRef";
import {VisuallyHidden as $kK41C$VisuallyHidden} from "react-aria/VisuallyHidden";

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
















const $0ff6aa0fd4e52420$export$a79eda4ff50e30b6 = /*#__PURE__*/ (0, $kK41C$createContext)(null);
const $0ff6aa0fd4e52420$export$b118023277d4a5c3 = /*#__PURE__*/ (0, $kK41C$createContext)(null);
const $0ff6aa0fd4e52420$export$29c6814b341e632b = /*#__PURE__*/ (0, $kK41C$createContext)(null);
const $0ff6aa0fd4e52420$export$29d84393af70866c = /*#__PURE__*/ (0, $kK41C$createContext)(null);
const $0ff6aa0fd4e52420$export$a98f0dcb43a68a25 = /*#__PURE__*/ (0, $kK41C$forwardRef)(function RadioGroup(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $0ff6aa0fd4e52420$export$a79eda4ff50e30b6);
    let { validationBehavior: formValidationBehavior } = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)((0, $c7332d4a2d191cd2$export$c24727297075ec6a)) || {};
    var _props_validationBehavior, _ref;
    let validationBehavior = (_ref = (_props_validationBehavior = props.validationBehavior) !== null && _props_validationBehavior !== void 0 ? _props_validationBehavior : formValidationBehavior) !== null && _ref !== void 0 ? _ref : 'native';
    let state = (0, $kK41C$useRadioGroupState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let [labelRef, label] = (0, $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { radioGroupProps: radioGroupProps, labelProps: labelProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $kK41C$useRadioGroup)({
        ...props,
        label: label,
        validationBehavior: validationBehavior
    }, state);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $kK41C$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $kK41C$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $kK41C$mergeProps)(DOMProps, renderProps, radioGroupProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-orientation": props.orientation || 'vertical',
        "data-invalid": state.isInvalid || undefined,
        "data-disabled": state.isDisabled || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-required": state.isRequired || undefined
    }, /*#__PURE__*/ (0, $kK41C$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $0ff6aa0fd4e52420$export$29d84393af70866c,
                state
            ],
            [
                (0, $3e4839e5b30e7b17$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef,
                    elementType: 'span'
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
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, $kK41C$react).createElement((0, $347bc273c4058e94$export$758399f318e6385a), null, renderProps.children)));
});
const $0ff6aa0fd4e52420$export$d7b12c4107be0d61 = /*#__PURE__*/ (0, $kK41C$forwardRef)(function Radio(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(otherProps, ref, $0ff6aa0fd4e52420$export$b118023277d4a5c3);
    let state = (0, $kK41C$react).useContext($0ff6aa0fd4e52420$export$29d84393af70866c);
    let inputRef = (0, $kK41C$useObjectRef)((0, $kK41C$useMemo)(()=>(0, $kK41C$mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null), [
        userProvidedInputRef,
        props.inputRef
    ]));
    let aria = (0, $kK41C$useRadio)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        // ReactNode type doesn't allow function children.
        children: typeof props.children === 'function' ? true : props.children
    }, state, inputRef);
    return /*#__PURE__*/ (0, $kK41C$react).createElement($0ff6aa0fd4e52420$var$InternalRadioContext.Provider, {
        value: {
            ...aria,
            inputRef: inputRef,
            defaultClassName: 'react-aria-Radio'
        }
    }, /*#__PURE__*/ (0, $kK41C$react).createElement($0ff6aa0fd4e52420$export$f4422ae58352e179, {
        ...props,
        ref: ref
    }));
});
const $0ff6aa0fd4e52420$var$InternalRadioContext = /*#__PURE__*/ (0, $kK41C$createContext)(null);
const $0ff6aa0fd4e52420$export$4aaf0c609b3e241e = /*#__PURE__*/ (0, $kK41C$forwardRef)(function RadioField(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(otherProps, ref, $0ff6aa0fd4e52420$export$29c6814b341e632b);
    let state = (0, $kK41C$react).useContext($0ff6aa0fd4e52420$export$29d84393af70866c);
    let inputRef = (0, $kK41C$useObjectRef)((0, $kK41C$useMemo)(()=>(0, $kK41C$mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null), [
        userProvidedInputRef,
        props.inputRef
    ]));
    let aria = (0, $kK41C$useRadio)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        // ReactNode type doesn't allow function children.
        children: typeof props.children === 'function' ? true : props.children
    }, state, inputRef);
    let { descriptionProps: descriptionProps, isSelected: isSelected, isDisabled: isDisabled } = aria;
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $kK41C$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $kK41C$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $kK41C$mergeProps)(DOMProps, renderProps),
        ref: ref,
        "data-selected": isSelected || undefined,
        "data-disabled": isDisabled || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-required": state.isRequired || undefined
    }, /*#__PURE__*/ (0, $kK41C$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                (0, $0d6f83ad40839938$export$c9549807523555e0),
                {
                    isSelected: isSelected
                }
            ],
            [
                $0ff6aa0fd4e52420$var$InternalRadioContext,
                {
                    ...aria,
                    inputRef: inputRef,
                    defaultClassName: 'react-aria-RadioButton'
                }
            ],
            [
                (0, $20d769b1e2b13352$export$9afb8bc826b033ea),
                {
                    slots: {
                        description: descriptionProps
                    }
                }
            ]
        ]
    }, renderProps.children));
});
const $0ff6aa0fd4e52420$export$f4422ae58352e179 = /*#__PURE__*/ (0, $kK41C$forwardRef)(function RadioButton(props, ref) {
    let { labelProps: labelProps, inputProps: inputProps, isSelected: isSelected, isDisabled: isDisabled, isPressed: isPressed, defaultClassName: defaultClassName, inputRef: inputRef } = (0, $kK41C$useContext)($0ff6aa0fd4e52420$var$InternalRadioContext);
    let state = (0, $kK41C$react).useContext($0ff6aa0fd4e52420$export$29d84393af70866c);
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $kK41C$useFocusRing)();
    let interactionDisabled = isDisabled || state.isReadOnly;
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $kK41C$useHover)({
        ...props,
        isDisabled: interactionDisabled
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
            isReadOnly: state.isReadOnly,
            isInvalid: state.isInvalid,
            isRequired: state.isRequired
        }
    });
    let DOMProps = (0, $kK41C$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $kK41C$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).label, {
        ...(0, $kK41C$mergeProps)(DOMProps, labelProps, hoverProps, renderProps),
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
    }, /*#__PURE__*/ (0, $kK41C$react).createElement((0, $kK41C$VisuallyHidden), {
        elementType: "span"
    }, /*#__PURE__*/ (0, $kK41C$react).createElement("input", {
        ...(0, $kK41C$mergeProps)(inputProps, focusProps),
        ref: inputRef
    })), renderProps.children);
});


export {$0ff6aa0fd4e52420$export$a79eda4ff50e30b6 as RadioGroupContext, $0ff6aa0fd4e52420$export$b118023277d4a5c3 as RadioContext, $0ff6aa0fd4e52420$export$29c6814b341e632b as RadioFieldContext, $0ff6aa0fd4e52420$export$29d84393af70866c as RadioGroupStateContext, $0ff6aa0fd4e52420$export$a98f0dcb43a68a25 as RadioGroup, $0ff6aa0fd4e52420$export$d7b12c4107be0d61 as Radio, $0ff6aa0fd4e52420$export$f4422ae58352e179 as RadioButton, $0ff6aa0fd4e52420$export$4aaf0c609b3e241e as RadioField};
//# sourceMappingURL=RadioGroup.js.map
