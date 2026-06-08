var $048d76b84370f141$exports = require("./utils.cjs");
var $862aa7df04d8fa76$exports = require("./FieldError.cjs");
var $5adc12e2ce73be8f$exports = require("./Form.cjs");
var $d5d46822336ca1e1$exports = require("./Label.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $c6KLw$reactariauseCheckboxGroup = require("react-aria/useCheckboxGroup");
var $c6KLw$reactariauseCheckbox = require("react-aria/useCheckbox");
var $c6KLw$reactstatelyuseCheckboxGroupState = require("react-stately/useCheckboxGroupState");
var $c6KLw$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $c6KLw$reactariamergeProps = require("react-aria/mergeProps");
var $c6KLw$reactariamergeRefs = require("react-aria/mergeRefs");
var $c6KLw$react = require("react");
var $c6KLw$reactariauseFocusRing = require("react-aria/useFocusRing");
var $c6KLw$reactariauseHover = require("react-aria/useHover");
var $c6KLw$reactariauseObjectRef = require("react-aria/useObjectRef");
var $c6KLw$reactstatelyuseToggleState = require("react-stately/useToggleState");
var $c6KLw$reactariaVisuallyHidden = require("react-aria/VisuallyHidden");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "CheckboxContext", function () { return $365d89633c2041bc$export$b085522c77523c51; });
$parcel$export(module.exports, "CheckboxFieldContext", function () { return $365d89633c2041bc$export$c32003b803b6c22e; });
$parcel$export(module.exports, "CheckboxGroupContext", function () { return $365d89633c2041bc$export$baf37c4be89255b8; });
$parcel$export(module.exports, "CheckboxGroupStateContext", function () { return $365d89633c2041bc$export$139c5b8563afc1fc; });
$parcel$export(module.exports, "CheckboxGroup", function () { return $365d89633c2041bc$export$4aa08d5625cb8ead; });
$parcel$export(module.exports, "CheckboxField", function () { return $365d89633c2041bc$export$94195a47b94ed396; });
$parcel$export(module.exports, "Checkbox", function () { return $365d89633c2041bc$export$48513f6b9f8ce62d; });
$parcel$export(module.exports, "CheckboxButton", function () { return $365d89633c2041bc$export$6e7a18c0548f3129; });
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
















const $365d89633c2041bc$export$b085522c77523c51 = /*#__PURE__*/ (0, $c6KLw$react.createContext)(null);
const $365d89633c2041bc$export$c32003b803b6c22e = /*#__PURE__*/ (0, $c6KLw$react.createContext)(null);
const $365d89633c2041bc$export$baf37c4be89255b8 = /*#__PURE__*/ (0, $c6KLw$react.createContext)(null);
const $365d89633c2041bc$export$139c5b8563afc1fc = /*#__PURE__*/ (0, $c6KLw$react.createContext)(null);
const $365d89633c2041bc$export$4aa08d5625cb8ead = /*#__PURE__*/ (0, $c6KLw$react.forwardRef)(function CheckboxGroup(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $365d89633c2041bc$export$baf37c4be89255b8);
    let { validationBehavior: formValidationBehavior } = (0, $048d76b84370f141$exports.useSlottedContext)((0, $5adc12e2ce73be8f$exports.FormContext)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let state = (0, $c6KLw$reactstatelyuseCheckboxGroupState.useCheckboxGroupState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let { groupProps: groupProps, labelProps: labelProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $c6KLw$reactariauseCheckboxGroup.useCheckboxGroup)({
        ...props,
        label: label,
        validationBehavior: validationBehavior
    }, state);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $c6KLw$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($c6KLw$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $c6KLw$reactariamergeProps.mergeProps)(DOMProps, renderProps, groupProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-required": props.isRequired || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($c6KLw$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $365d89633c2041bc$export$139c5b8563afc1fc,
                state
            ],
            [
                (0, $d5d46822336ca1e1$exports.LabelContext),
                {
                    ...labelProps,
                    ref: labelRef,
                    elementType: 'span'
                }
            ],
            [
                (0, $cab7d9a238d19c33$exports.TextContext),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $862aa7df04d8fa76$exports.FieldErrorContext),
                validation
            ]
        ]
    }, renderProps.children));
});
const $365d89633c2041bc$var$InternalCheckboxContext = /*#__PURE__*/ (0, $c6KLw$react.createContext)(null);
const $365d89633c2041bc$export$94195a47b94ed396 = /*#__PURE__*/ (0, $c6KLw$react.forwardRef)(function Checkbox(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(otherProps, ref, $365d89633c2041bc$export$c32003b803b6c22e);
    let groupState = (0, $c6KLw$react.useContext)($365d89633c2041bc$export$139c5b8563afc1fc);
    let [aria, inputRef] = $365d89633c2041bc$var$useCheckboxAria(props, userProvidedInputRef);
    let { descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isSelected: isSelected, isDisabled: isDisabled, isReadOnly: isReadOnly, isInvalid: isInvalid, validationDetails: validationDetails, validationErrors: validationErrors } = aria;
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $c6KLw$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($c6KLw$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $c6KLw$reactariamergeProps.mergeProps)(DOMProps, renderProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-selected": isSelected || undefined,
        "data-indeterminate": props.isIndeterminate || undefined,
        "data-disabled": isDisabled || undefined,
        "data-readonly": isReadOnly || undefined,
        "data-invalid": isInvalid || undefined,
        "data-required": props.isRequired || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($c6KLw$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $365d89633c2041bc$var$InternalCheckboxContext,
                {
                    ...aria,
                    inputRef: inputRef,
                    defaultClassName: 'react-aria-CheckboxButton',
                    isIndeterminate: props.isIndeterminate,
                    isRequired: props.isRequired
                }
            ],
            [
                (0, $cab7d9a238d19c33$exports.TextContext),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            // In a CheckboxGroup, validation is handled at the group level instead of repeated on each checkbox.
            [
                (0, $862aa7df04d8fa76$exports.FieldErrorContext),
                groupState ? null : {
                    isInvalid: isInvalid,
                    validationDetails: validationDetails,
                    validationErrors: validationErrors
                }
            ]
        ]
    }, renderProps.children));
});
function $365d89633c2041bc$var$useCheckboxAria(props, userProvidedInputRef) {
    let { validationBehavior: formValidationBehavior } = (0, $048d76b84370f141$exports.useSlottedContext)((0, $5adc12e2ce73be8f$exports.FormContext)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let groupState = (0, $c6KLw$react.useContext)($365d89633c2041bc$export$139c5b8563afc1fc);
    let inputRef = (0, $c6KLw$reactariauseObjectRef.useObjectRef)((0, $c6KLw$react.useMemo)(()=>(0, $c6KLw$reactariamergeRefs.mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null), [
        userProvidedInputRef,
        props.inputRef
    ]));
    let checkboxProps = {
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        children: typeof props.children === 'function' ? true : props.children,
        value: props.value,
        validationBehavior: validationBehavior
    };
    let aria = groupState ? (0, $c6KLw$reactariauseCheckboxGroup.useCheckboxGroupItem)(checkboxProps, groupState, inputRef) : (0, $c6KLw$reactariauseCheckbox.useCheckbox)(checkboxProps, (0, $c6KLw$reactstatelyuseToggleState.useToggleState)(props), inputRef);
    return [
        aria,
        inputRef
    ];
}
const $365d89633c2041bc$export$48513f6b9f8ce62d = /*#__PURE__*/ (0, $c6KLw$react.forwardRef)(function Checkbox(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(otherProps, ref, $365d89633c2041bc$export$b085522c77523c51);
    let [aria, inputRef] = $365d89633c2041bc$var$useCheckboxAria(props, userProvidedInputRef);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($c6KLw$react))).createElement($365d89633c2041bc$var$InternalCheckboxContext.Provider, {
        value: {
            ...aria,
            inputRef: inputRef,
            defaultClassName: 'react-aria-Checkbox',
            isIndeterminate: props.isIndeterminate,
            isRequired: props.isRequired
        }
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($c6KLw$react))).createElement($365d89633c2041bc$export$6e7a18c0548f3129, {
        ...props,
        ref: ref
    }));
});
const $365d89633c2041bc$export$6e7a18c0548f3129 = /*#__PURE__*/ (0, $c6KLw$react.forwardRef)(function CheckboxButton(props, ref) {
    let { labelProps: labelProps, inputProps: inputProps, isSelected: isSelected, isDisabled: isDisabled, isReadOnly: isReadOnly, isPressed: isPressed, isInvalid: isInvalid, inputRef: inputRef, defaultClassName: defaultClassName, isIndeterminate: isIndeterminate, isRequired: isRequired } = (0, $c6KLw$react.useContext)($365d89633c2041bc$var$InternalCheckboxContext);
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $c6KLw$reactariauseFocusRing.useFocusRing)();
    let isInteractionDisabled = isDisabled || isReadOnly;
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $c6KLw$reactariauseHover.useHover)({
        ...props,
        isDisabled: isInteractionDisabled
    });
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $c6KLw$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($c6KLw$react))).createElement((0, $048d76b84370f141$exports.dom).label, {
        ...(0, $c6KLw$reactariamergeProps.mergeProps)(DOMProps, labelProps, hoverProps, renderProps),
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
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($c6KLw$react))).createElement((0, $c6KLw$reactariaVisuallyHidden.VisuallyHidden), {
        elementType: "span"
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($c6KLw$react))).createElement("input", {
        ...(0, $c6KLw$reactariamergeProps.mergeProps)(inputProps, focusProps),
        ref: inputRef
    })), renderProps.children);
});


//# sourceMappingURL=Checkbox.cjs.map
