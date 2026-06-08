var $048d76b84370f141$exports = require("./utils.cjs");
var $862aa7df04d8fa76$exports = require("./FieldError.cjs");
var $5adc12e2ce73be8f$exports = require("./Form.cjs");
var $d5d46822336ca1e1$exports = require("./Label.cjs");
var $61557b2a9b2862a8$exports = require("./SelectionIndicator.cjs");
var $9a60bd90621ebc78$exports = require("./SharedElementTransition.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $kyCvN$reactariauseRadioGroup = require("react-aria/useRadioGroup");
var $kyCvN$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $kyCvN$reactariamergeProps = require("react-aria/mergeProps");
var $kyCvN$reactariamergeRefs = require("react-aria/mergeRefs");
var $kyCvN$reactstatelyuseRadioGroupState = require("react-stately/useRadioGroupState");
var $kyCvN$react = require("react");
var $kyCvN$reactariauseFocusRing = require("react-aria/useFocusRing");
var $kyCvN$reactariauseHover = require("react-aria/useHover");
var $kyCvN$reactariauseObjectRef = require("react-aria/useObjectRef");
var $kyCvN$reactariaVisuallyHidden = require("react-aria/VisuallyHidden");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "RadioGroupContext", function () { return $ecfe484c0a342e33$export$a79eda4ff50e30b6; });
$parcel$export(module.exports, "RadioContext", function () { return $ecfe484c0a342e33$export$b118023277d4a5c3; });
$parcel$export(module.exports, "RadioFieldContext", function () { return $ecfe484c0a342e33$export$29c6814b341e632b; });
$parcel$export(module.exports, "RadioGroupStateContext", function () { return $ecfe484c0a342e33$export$29d84393af70866c; });
$parcel$export(module.exports, "RadioGroup", function () { return $ecfe484c0a342e33$export$a98f0dcb43a68a25; });
$parcel$export(module.exports, "Radio", function () { return $ecfe484c0a342e33$export$d7b12c4107be0d61; });
$parcel$export(module.exports, "RadioButton", function () { return $ecfe484c0a342e33$export$f4422ae58352e179; });
$parcel$export(module.exports, "RadioField", function () { return $ecfe484c0a342e33$export$4aaf0c609b3e241e; });
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
















const $ecfe484c0a342e33$export$a79eda4ff50e30b6 = /*#__PURE__*/ (0, $kyCvN$react.createContext)(null);
const $ecfe484c0a342e33$export$b118023277d4a5c3 = /*#__PURE__*/ (0, $kyCvN$react.createContext)(null);
const $ecfe484c0a342e33$export$29c6814b341e632b = /*#__PURE__*/ (0, $kyCvN$react.createContext)(null);
const $ecfe484c0a342e33$export$29d84393af70866c = /*#__PURE__*/ (0, $kyCvN$react.createContext)(null);
const $ecfe484c0a342e33$export$a98f0dcb43a68a25 = /*#__PURE__*/ (0, $kyCvN$react.forwardRef)(function RadioGroup(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $ecfe484c0a342e33$export$a79eda4ff50e30b6);
    let { validationBehavior: formValidationBehavior } = (0, $048d76b84370f141$exports.useSlottedContext)((0, $5adc12e2ce73be8f$exports.FormContext)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let state = (0, $kyCvN$reactstatelyuseRadioGroupState.useRadioGroupState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let { radioGroupProps: radioGroupProps, labelProps: labelProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $kyCvN$reactariauseRadioGroup.useRadioGroup)({
        ...props,
        label: label,
        validationBehavior: validationBehavior
    }, state);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $kyCvN$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($kyCvN$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $kyCvN$reactariamergeProps.mergeProps)(DOMProps, renderProps, radioGroupProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-orientation": props.orientation || 'vertical',
        "data-invalid": state.isInvalid || undefined,
        "data-disabled": state.isDisabled || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-required": state.isRequired || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($kyCvN$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $ecfe484c0a342e33$export$29d84393af70866c,
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
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($kyCvN$react))).createElement((0, $9a60bd90621ebc78$exports.SharedElementTransition), null, renderProps.children)));
});
const $ecfe484c0a342e33$export$d7b12c4107be0d61 = /*#__PURE__*/ (0, $kyCvN$react.forwardRef)(function Radio(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(otherProps, ref, $ecfe484c0a342e33$export$b118023277d4a5c3);
    let state = (0, ($parcel$interopDefault($kyCvN$react))).useContext($ecfe484c0a342e33$export$29d84393af70866c);
    let inputRef = (0, $kyCvN$reactariauseObjectRef.useObjectRef)((0, $kyCvN$react.useMemo)(()=>(0, $kyCvN$reactariamergeRefs.mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null), [
        userProvidedInputRef,
        props.inputRef
    ]));
    let aria = (0, $kyCvN$reactariauseRadioGroup.useRadio)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        // ReactNode type doesn't allow function children.
        children: typeof props.children === 'function' ? true : props.children
    }, state, inputRef);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($kyCvN$react))).createElement($ecfe484c0a342e33$var$InternalRadioContext.Provider, {
        value: {
            ...aria,
            inputRef: inputRef,
            defaultClassName: 'react-aria-Radio'
        }
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($kyCvN$react))).createElement($ecfe484c0a342e33$export$f4422ae58352e179, {
        ...props,
        ref: ref
    }));
});
const $ecfe484c0a342e33$var$InternalRadioContext = /*#__PURE__*/ (0, $kyCvN$react.createContext)(null);
const $ecfe484c0a342e33$export$4aaf0c609b3e241e = /*#__PURE__*/ (0, $kyCvN$react.forwardRef)(function RadioField(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(otherProps, ref, $ecfe484c0a342e33$export$29c6814b341e632b);
    let state = (0, ($parcel$interopDefault($kyCvN$react))).useContext($ecfe484c0a342e33$export$29d84393af70866c);
    let inputRef = (0, $kyCvN$reactariauseObjectRef.useObjectRef)((0, $kyCvN$react.useMemo)(()=>(0, $kyCvN$reactariamergeRefs.mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null), [
        userProvidedInputRef,
        props.inputRef
    ]));
    let aria = (0, $kyCvN$reactariauseRadioGroup.useRadio)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        // ReactNode type doesn't allow function children.
        children: typeof props.children === 'function' ? true : props.children
    }, state, inputRef);
    let { descriptionProps: descriptionProps, isSelected: isSelected, isDisabled: isDisabled } = aria;
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $kyCvN$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($kyCvN$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $kyCvN$reactariamergeProps.mergeProps)(DOMProps, renderProps),
        ref: ref,
        "data-selected": isSelected || undefined,
        "data-disabled": isDisabled || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-required": state.isRequired || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($kyCvN$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                (0, $61557b2a9b2862a8$exports.SelectionIndicatorContext),
                {
                    isSelected: isSelected
                }
            ],
            [
                $ecfe484c0a342e33$var$InternalRadioContext,
                {
                    ...aria,
                    inputRef: inputRef,
                    defaultClassName: 'react-aria-RadioButton'
                }
            ],
            [
                (0, $cab7d9a238d19c33$exports.TextContext),
                {
                    slots: {
                        description: descriptionProps
                    }
                }
            ]
        ]
    }, renderProps.children));
});
const $ecfe484c0a342e33$export$f4422ae58352e179 = /*#__PURE__*/ (0, $kyCvN$react.forwardRef)(function RadioButton(props, ref) {
    let { labelProps: labelProps, inputProps: inputProps, isSelected: isSelected, isDisabled: isDisabled, isPressed: isPressed, defaultClassName: defaultClassName, inputRef: inputRef } = (0, $kyCvN$react.useContext)($ecfe484c0a342e33$var$InternalRadioContext);
    let state = (0, ($parcel$interopDefault($kyCvN$react))).useContext($ecfe484c0a342e33$export$29d84393af70866c);
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $kyCvN$reactariauseFocusRing.useFocusRing)();
    let interactionDisabled = isDisabled || state.isReadOnly;
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $kyCvN$reactariauseHover.useHover)({
        ...props,
        isDisabled: interactionDisabled
    });
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $kyCvN$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($kyCvN$react))).createElement((0, $048d76b84370f141$exports.dom).label, {
        ...(0, $kyCvN$reactariamergeProps.mergeProps)(DOMProps, labelProps, hoverProps, renderProps),
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
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($kyCvN$react))).createElement((0, $kyCvN$reactariaVisuallyHidden.VisuallyHidden), {
        elementType: "span"
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($kyCvN$react))).createElement("input", {
        ...(0, $kyCvN$reactariamergeProps.mergeProps)(inputProps, focusProps),
        ref: inputRef
    })), renderProps.children);
});


//# sourceMappingURL=RadioGroup.cjs.map
