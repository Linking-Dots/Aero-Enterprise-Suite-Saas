import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, removeDataAttributes as $7230ffa83bc0c2cf$export$ef03459518577ad4, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlot as $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {FieldErrorContext as $1f3c3b1a70cec653$export$ff05c3ac10437e03} from "./FieldError.mjs";
import {FormContext as $cdaed739b1139372$export$c24727297075ec6a} from "./Form.mjs";
import {LabelContext as $43a3b93638fe5db9$export$75b6ee27786ba447} from "./Label.mjs";
import {TextContext as $efe09c6d1c304b50$export$9afb8bc826b033ea} from "./Text.mjs";
import {useCheckboxGroup as $j1L6B$useCheckboxGroup, useCheckboxGroupItem as $j1L6B$useCheckboxGroupItem} from "react-aria/useCheckboxGroup";
import {useCheckbox as $j1L6B$useCheckbox} from "react-aria/useCheckbox";
import {useCheckboxGroupState as $j1L6B$useCheckboxGroupState} from "react-stately/useCheckboxGroupState";
import {filterDOMProps as $j1L6B$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $j1L6B$mergeProps} from "react-aria/mergeProps";
import {mergeRefs as $j1L6B$mergeRefs} from "react-aria/mergeRefs";
import $j1L6B$react, {createContext as $j1L6B$createContext, forwardRef as $j1L6B$forwardRef, useContext as $j1L6B$useContext, useMemo as $j1L6B$useMemo} from "react";
import {useFocusRing as $j1L6B$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $j1L6B$useHover} from "react-aria/useHover";
import {useObjectRef as $j1L6B$useObjectRef} from "react-aria/useObjectRef";
import {useToggleState as $j1L6B$useToggleState} from "react-stately/useToggleState";
import {VisuallyHidden as $j1L6B$VisuallyHidden} from "react-aria/VisuallyHidden";

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
















const $ed8ccb2e23e76301$export$b085522c77523c51 = /*#__PURE__*/ (0, $j1L6B$createContext)(null);
const $ed8ccb2e23e76301$export$c32003b803b6c22e = /*#__PURE__*/ (0, $j1L6B$createContext)(null);
const $ed8ccb2e23e76301$export$baf37c4be89255b8 = /*#__PURE__*/ (0, $j1L6B$createContext)(null);
const $ed8ccb2e23e76301$export$139c5b8563afc1fc = /*#__PURE__*/ (0, $j1L6B$createContext)(null);
const $ed8ccb2e23e76301$export$4aa08d5625cb8ead = /*#__PURE__*/ (0, $j1L6B$forwardRef)(function CheckboxGroup(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $ed8ccb2e23e76301$export$baf37c4be89255b8);
    let { validationBehavior: formValidationBehavior } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)((0, $cdaed739b1139372$export$c24727297075ec6a)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let state = (0, $j1L6B$useCheckboxGroupState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { groupProps: groupProps, labelProps: labelProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $j1L6B$useCheckboxGroup)({
        ...props,
        label: label,
        validationBehavior: validationBehavior
    }, state);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            isDisabled: state.isDisabled,
            isReadOnly: state.isReadOnly,
            isRequired: props.isRequired || false,
            isInvalid: state.isInvalid,
            state: state
        },
        defaultClassName: 'react-aria-CheckboxGroup'
    });
    let DOMProps = (0, $j1L6B$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $j1L6B$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $j1L6B$mergeProps)(DOMProps, renderProps, groupProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-required": props.isRequired || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, $j1L6B$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $ed8ccb2e23e76301$export$139c5b8563afc1fc,
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
    }, renderProps.children));
});
const $ed8ccb2e23e76301$var$InternalCheckboxContext = /*#__PURE__*/ (0, $j1L6B$createContext)(null);
const $ed8ccb2e23e76301$export$94195a47b94ed396 = /*#__PURE__*/ (0, $j1L6B$forwardRef)(function Checkbox(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(otherProps, ref, $ed8ccb2e23e76301$export$c32003b803b6c22e);
    let groupState = (0, $j1L6B$useContext)($ed8ccb2e23e76301$export$139c5b8563afc1fc);
    let [aria, inputRef] = $ed8ccb2e23e76301$var$useCheckboxAria(props, userProvidedInputRef);
    let { descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isSelected: isSelected, isDisabled: isDisabled, isReadOnly: isReadOnly, isInvalid: isInvalid, validationDetails: validationDetails, validationErrors: validationErrors } = aria;
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-CheckboxField',
        values: {
            isSelected: isSelected,
            isIndeterminate: props.isIndeterminate || false,
            isDisabled: isDisabled,
            isReadOnly: isReadOnly,
            isInvalid: isInvalid,
            isRequired: props.isRequired || false
        }
    });
    let DOMProps = (0, $j1L6B$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $j1L6B$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $j1L6B$mergeProps)(DOMProps, renderProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-selected": isSelected || undefined,
        "data-indeterminate": props.isIndeterminate || undefined,
        "data-disabled": isDisabled || undefined,
        "data-readonly": isReadOnly || undefined,
        "data-invalid": isInvalid || undefined,
        "data-required": props.isRequired || undefined
    }, /*#__PURE__*/ (0, $j1L6B$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $ed8ccb2e23e76301$var$InternalCheckboxContext,
                {
                    ...aria,
                    inputRef: inputRef,
                    defaultClassName: 'react-aria-CheckboxButton',
                    isIndeterminate: props.isIndeterminate,
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
            // In a CheckboxGroup, validation is handled at the group level instead of repeated on each checkbox.
            [
                (0, $1f3c3b1a70cec653$export$ff05c3ac10437e03),
                groupState ? null : {
                    isInvalid: isInvalid,
                    validationDetails: validationDetails,
                    validationErrors: validationErrors
                }
            ]
        ]
    }, renderProps.children));
});
function $ed8ccb2e23e76301$var$useCheckboxAria(props, userProvidedInputRef) {
    let { validationBehavior: formValidationBehavior } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)((0, $cdaed739b1139372$export$c24727297075ec6a)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let groupState = (0, $j1L6B$useContext)($ed8ccb2e23e76301$export$139c5b8563afc1fc);
    let inputRef = (0, $j1L6B$useObjectRef)((0, $j1L6B$useMemo)(()=>(0, $j1L6B$mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null), [
        userProvidedInputRef,
        props.inputRef
    ]));
    let checkboxProps = {
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        children: typeof props.children === 'function' ? true : props.children,
        value: props.value,
        validationBehavior: validationBehavior
    };
    let aria = groupState ? (0, $j1L6B$useCheckboxGroupItem)(checkboxProps, groupState, inputRef) : (0, $j1L6B$useCheckbox)(checkboxProps, (0, $j1L6B$useToggleState)(props), inputRef);
    return [
        aria,
        inputRef
    ];
}
const $ed8ccb2e23e76301$export$48513f6b9f8ce62d = /*#__PURE__*/ (0, $j1L6B$forwardRef)(function Checkbox(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(otherProps, ref, $ed8ccb2e23e76301$export$b085522c77523c51);
    let [aria, inputRef] = $ed8ccb2e23e76301$var$useCheckboxAria(props, userProvidedInputRef);
    return /*#__PURE__*/ (0, $j1L6B$react).createElement($ed8ccb2e23e76301$var$InternalCheckboxContext.Provider, {
        value: {
            ...aria,
            inputRef: inputRef,
            defaultClassName: 'react-aria-Checkbox',
            isIndeterminate: props.isIndeterminate,
            isRequired: props.isRequired
        }
    }, /*#__PURE__*/ (0, $j1L6B$react).createElement($ed8ccb2e23e76301$export$6e7a18c0548f3129, {
        ...props,
        ref: ref
    }));
});
const $ed8ccb2e23e76301$export$6e7a18c0548f3129 = /*#__PURE__*/ (0, $j1L6B$forwardRef)(function CheckboxButton(props, ref) {
    let { labelProps: labelProps, inputProps: inputProps, isSelected: isSelected, isDisabled: isDisabled, isReadOnly: isReadOnly, isPressed: isPressed, isInvalid: isInvalid, inputRef: inputRef, defaultClassName: defaultClassName, isIndeterminate: isIndeterminate, isRequired: isRequired } = (0, $j1L6B$useContext)($ed8ccb2e23e76301$var$InternalCheckboxContext);
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $j1L6B$useFocusRing)();
    let isInteractionDisabled = isDisabled || isReadOnly;
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $j1L6B$useHover)({
        ...props,
        isDisabled: isInteractionDisabled
    });
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: defaultClassName,
        values: {
            isSelected: isSelected,
            isIndeterminate: isIndeterminate || false,
            isPressed: isPressed,
            isHovered: isHovered,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: isDisabled,
            isReadOnly: isReadOnly,
            isInvalid: isInvalid,
            isRequired: isRequired || false
        }
    });
    let DOMProps = (0, $j1L6B$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $j1L6B$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).label, {
        ...(0, $j1L6B$mergeProps)(DOMProps, labelProps, hoverProps, renderProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-selected": isSelected || undefined,
        "data-indeterminate": isIndeterminate || undefined,
        "data-pressed": isPressed || undefined,
        "data-hovered": isHovered || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": isDisabled || undefined,
        "data-readonly": isReadOnly || undefined,
        "data-invalid": isInvalid || undefined,
        "data-required": isRequired || undefined
    }, /*#__PURE__*/ (0, $j1L6B$react).createElement((0, $j1L6B$VisuallyHidden), {
        elementType: "span"
    }, /*#__PURE__*/ (0, $j1L6B$react).createElement("input", {
        ...(0, $j1L6B$mergeProps)(inputProps, focusProps),
        ref: inputRef
    })), renderProps.children);
});


export {$ed8ccb2e23e76301$export$b085522c77523c51 as CheckboxContext, $ed8ccb2e23e76301$export$c32003b803b6c22e as CheckboxFieldContext, $ed8ccb2e23e76301$export$baf37c4be89255b8 as CheckboxGroupContext, $ed8ccb2e23e76301$export$139c5b8563afc1fc as CheckboxGroupStateContext, $ed8ccb2e23e76301$export$4aa08d5625cb8ead as CheckboxGroup, $ed8ccb2e23e76301$export$94195a47b94ed396 as CheckboxField, $ed8ccb2e23e76301$export$48513f6b9f8ce62d as Checkbox, $ed8ccb2e23e76301$export$6e7a18c0548f3129 as CheckboxButton};
//# sourceMappingURL=Checkbox.mjs.map
