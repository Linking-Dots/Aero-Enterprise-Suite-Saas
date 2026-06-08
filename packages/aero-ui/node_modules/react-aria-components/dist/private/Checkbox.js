import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, removeDataAttributes as $b7b7a92703138c9b$export$ef03459518577ad4, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3, useSlot as $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8, useSlottedContext as $b7b7a92703138c9b$export$fabf2dc03a41866e} from "./utils.js";
import {FieldErrorContext as $6567560e1d9cc847$export$ff05c3ac10437e03} from "./FieldError.js";
import {FormContext as $c7332d4a2d191cd2$export$c24727297075ec6a} from "./Form.js";
import {LabelContext as $3e4839e5b30e7b17$export$75b6ee27786ba447} from "./Label.js";
import {TextContext as $20d769b1e2b13352$export$9afb8bc826b033ea} from "./Text.js";
import {useCheckboxGroup as $1HTEy$useCheckboxGroup, useCheckboxGroupItem as $1HTEy$useCheckboxGroupItem} from "react-aria/useCheckboxGroup";
import {useCheckbox as $1HTEy$useCheckbox} from "react-aria/useCheckbox";
import {useCheckboxGroupState as $1HTEy$useCheckboxGroupState} from "react-stately/useCheckboxGroupState";
import {filterDOMProps as $1HTEy$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $1HTEy$mergeProps} from "react-aria/mergeProps";
import {mergeRefs as $1HTEy$mergeRefs} from "react-aria/mergeRefs";
import $1HTEy$react, {createContext as $1HTEy$createContext, forwardRef as $1HTEy$forwardRef, useContext as $1HTEy$useContext, useMemo as $1HTEy$useMemo} from "react";
import {useFocusRing as $1HTEy$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $1HTEy$useHover} from "react-aria/useHover";
import {useObjectRef as $1HTEy$useObjectRef} from "react-aria/useObjectRef";
import {useToggleState as $1HTEy$useToggleState} from "react-stately/useToggleState";
import {VisuallyHidden as $1HTEy$VisuallyHidden} from "react-aria/VisuallyHidden";

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
















const $4bd9daf9bf54cf04$export$b085522c77523c51 = /*#__PURE__*/ (0, $1HTEy$createContext)(null);
const $4bd9daf9bf54cf04$export$c32003b803b6c22e = /*#__PURE__*/ (0, $1HTEy$createContext)(null);
const $4bd9daf9bf54cf04$export$baf37c4be89255b8 = /*#__PURE__*/ (0, $1HTEy$createContext)(null);
const $4bd9daf9bf54cf04$export$139c5b8563afc1fc = /*#__PURE__*/ (0, $1HTEy$createContext)(null);
const $4bd9daf9bf54cf04$export$4aa08d5625cb8ead = /*#__PURE__*/ (0, $1HTEy$forwardRef)(function CheckboxGroup(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $4bd9daf9bf54cf04$export$baf37c4be89255b8);
    let { validationBehavior: formValidationBehavior } = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)((0, $c7332d4a2d191cd2$export$c24727297075ec6a)) || {};
    var _props_validationBehavior, _ref;
    let validationBehavior = (_ref = (_props_validationBehavior = props.validationBehavior) !== null && _props_validationBehavior !== void 0 ? _props_validationBehavior : formValidationBehavior) !== null && _ref !== void 0 ? _ref : 'native';
    let state = (0, $1HTEy$useCheckboxGroupState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let [labelRef, label] = (0, $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { groupProps: groupProps, labelProps: labelProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $1HTEy$useCheckboxGroup)({
        ...props,
        label: label,
        validationBehavior: validationBehavior
    }, state);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $1HTEy$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $1HTEy$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $1HTEy$mergeProps)(DOMProps, renderProps, groupProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-required": props.isRequired || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, $1HTEy$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $4bd9daf9bf54cf04$export$139c5b8563afc1fc,
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
    }, renderProps.children));
});
const $4bd9daf9bf54cf04$var$InternalCheckboxContext = /*#__PURE__*/ (0, $1HTEy$createContext)(null);
const $4bd9daf9bf54cf04$export$94195a47b94ed396 = /*#__PURE__*/ (0, $1HTEy$forwardRef)(function Checkbox(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(otherProps, ref, $4bd9daf9bf54cf04$export$c32003b803b6c22e);
    let groupState = (0, $1HTEy$useContext)($4bd9daf9bf54cf04$export$139c5b8563afc1fc);
    let [aria, inputRef] = $4bd9daf9bf54cf04$var$useCheckboxAria(props, userProvidedInputRef);
    let { descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isSelected: isSelected, isDisabled: isDisabled, isReadOnly: isReadOnly, isInvalid: isInvalid, validationDetails: validationDetails, validationErrors: validationErrors } = aria;
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $1HTEy$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $1HTEy$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $1HTEy$mergeProps)(DOMProps, renderProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-selected": isSelected || undefined,
        "data-indeterminate": props.isIndeterminate || undefined,
        "data-disabled": isDisabled || undefined,
        "data-readonly": isReadOnly || undefined,
        "data-invalid": isInvalid || undefined,
        "data-required": props.isRequired || undefined
    }, /*#__PURE__*/ (0, $1HTEy$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $4bd9daf9bf54cf04$var$InternalCheckboxContext,
                {
                    ...aria,
                    inputRef: inputRef,
                    defaultClassName: 'react-aria-CheckboxButton',
                    isIndeterminate: props.isIndeterminate,
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
            // In a CheckboxGroup, validation is handled at the group level instead of repeated on each checkbox.
            [
                (0, $6567560e1d9cc847$export$ff05c3ac10437e03),
                groupState ? null : {
                    isInvalid: isInvalid,
                    validationDetails: validationDetails,
                    validationErrors: validationErrors
                }
            ]
        ]
    }, renderProps.children));
});
function $4bd9daf9bf54cf04$var$useCheckboxAria(props, userProvidedInputRef) {
    let { validationBehavior: formValidationBehavior } = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)((0, $c7332d4a2d191cd2$export$c24727297075ec6a)) || {};
    var _props_validationBehavior, _ref;
    let validationBehavior = (_ref = (_props_validationBehavior = props.validationBehavior) !== null && _props_validationBehavior !== void 0 ? _props_validationBehavior : formValidationBehavior) !== null && _ref !== void 0 ? _ref : 'native';
    let groupState = (0, $1HTEy$useContext)($4bd9daf9bf54cf04$export$139c5b8563afc1fc);
    let inputRef = (0, $1HTEy$useObjectRef)((0, $1HTEy$useMemo)(()=>(0, $1HTEy$mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null), [
        userProvidedInputRef,
        props.inputRef
    ]));
    let checkboxProps = {
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        children: typeof props.children === 'function' ? true : props.children,
        value: props.value,
        validationBehavior: validationBehavior
    };
    let aria = groupState ? (0, $1HTEy$useCheckboxGroupItem)(checkboxProps, groupState, inputRef) : (0, $1HTEy$useCheckbox)(checkboxProps, (0, $1HTEy$useToggleState)(props), inputRef);
    return [
        aria,
        inputRef
    ];
}
const $4bd9daf9bf54cf04$export$48513f6b9f8ce62d = /*#__PURE__*/ (0, $1HTEy$forwardRef)(function Checkbox(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(otherProps, ref, $4bd9daf9bf54cf04$export$b085522c77523c51);
    let [aria, inputRef] = $4bd9daf9bf54cf04$var$useCheckboxAria(props, userProvidedInputRef);
    return /*#__PURE__*/ (0, $1HTEy$react).createElement($4bd9daf9bf54cf04$var$InternalCheckboxContext.Provider, {
        value: {
            ...aria,
            inputRef: inputRef,
            defaultClassName: 'react-aria-Checkbox',
            isIndeterminate: props.isIndeterminate,
            isRequired: props.isRequired
        }
    }, /*#__PURE__*/ (0, $1HTEy$react).createElement($4bd9daf9bf54cf04$export$6e7a18c0548f3129, {
        ...props,
        ref: ref
    }));
});
const $4bd9daf9bf54cf04$export$6e7a18c0548f3129 = /*#__PURE__*/ (0, $1HTEy$forwardRef)(function CheckboxButton(props, ref) {
    let { labelProps: labelProps, inputProps: inputProps, isSelected: isSelected, isDisabled: isDisabled, isReadOnly: isReadOnly, isPressed: isPressed, isInvalid: isInvalid, inputRef: inputRef, defaultClassName: defaultClassName, isIndeterminate: isIndeterminate, isRequired: isRequired } = (0, $1HTEy$useContext)($4bd9daf9bf54cf04$var$InternalCheckboxContext);
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $1HTEy$useFocusRing)();
    let isInteractionDisabled = isDisabled || isReadOnly;
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $1HTEy$useHover)({
        ...props,
        isDisabled: isInteractionDisabled
    });
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $1HTEy$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $1HTEy$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).label, {
        ...(0, $1HTEy$mergeProps)(DOMProps, labelProps, hoverProps, renderProps),
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
    }, /*#__PURE__*/ (0, $1HTEy$react).createElement((0, $1HTEy$VisuallyHidden), {
        elementType: "span"
    }, /*#__PURE__*/ (0, $1HTEy$react).createElement("input", {
        ...(0, $1HTEy$mergeProps)(inputProps, focusProps),
        ref: inputRef
    })), renderProps.children);
});


export {$4bd9daf9bf54cf04$export$b085522c77523c51 as CheckboxContext, $4bd9daf9bf54cf04$export$c32003b803b6c22e as CheckboxFieldContext, $4bd9daf9bf54cf04$export$baf37c4be89255b8 as CheckboxGroupContext, $4bd9daf9bf54cf04$export$139c5b8563afc1fc as CheckboxGroupStateContext, $4bd9daf9bf54cf04$export$4aa08d5625cb8ead as CheckboxGroup, $4bd9daf9bf54cf04$export$94195a47b94ed396 as CheckboxField, $4bd9daf9bf54cf04$export$48513f6b9f8ce62d as Checkbox, $4bd9daf9bf54cf04$export$6e7a18c0548f3129 as CheckboxButton};
//# sourceMappingURL=Checkbox.js.map
