var $048d76b84370f141$exports = require("./utils.cjs");
var $862aa7df04d8fa76$exports = require("./FieldError.cjs");
var $5adc12e2ce73be8f$exports = require("./Form.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $goUg1$reactariauseSwitch = require("react-aria/useSwitch");
var $goUg1$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $goUg1$reactariamergeProps = require("react-aria/mergeProps");
var $goUg1$reactariamergeRefs = require("react-aria/mergeRefs");
var $goUg1$react = require("react");
var $goUg1$reactstatelyuseToggleState = require("react-stately/useToggleState");
var $goUg1$reactariauseFocusRing = require("react-aria/useFocusRing");
var $goUg1$reactariauseHover = require("react-aria/useHover");
var $goUg1$reactariauseObjectRef = require("react-aria/useObjectRef");
var $goUg1$reactariaVisuallyHidden = require("react-aria/VisuallyHidden");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "SwitchContext", function () { return $f78e777132fa7bc2$export$8699e3b644d5a28a; });
$parcel$export(module.exports, "SwitchFieldContext", function () { return $f78e777132fa7bc2$export$3e1405298f0cafda; });
$parcel$export(module.exports, "Switch", function () { return $f78e777132fa7bc2$export$b5d5cf8927ab7262; });
$parcel$export(module.exports, "SwitchButton", function () { return $f78e777132fa7bc2$export$72111f742dee7cb8; });
$parcel$export(module.exports, "SwitchField", function () { return $f78e777132fa7bc2$export$208c2e617baf9fc3; });
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













const $f78e777132fa7bc2$export$8699e3b644d5a28a = /*#__PURE__*/ (0, $goUg1$react.createContext)(null);
const $f78e777132fa7bc2$export$3e1405298f0cafda = /*#__PURE__*/ (0, $goUg1$react.createContext)(null);
const $f78e777132fa7bc2$export$5cc1518e1ec171c = /*#__PURE__*/ (0, $goUg1$react.createContext)(null);
const $f78e777132fa7bc2$export$b5d5cf8927ab7262 = /*#__PURE__*/ (0, $goUg1$react.forwardRef)(function Switch(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(otherProps, ref, $f78e777132fa7bc2$export$8699e3b644d5a28a);
    let inputRef = (0, $goUg1$reactariauseObjectRef.useObjectRef)((0, $goUg1$reactariamergeRefs.mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null));
    let state = (0, $goUg1$reactstatelyuseToggleState.useToggleState)(props);
    let aria = (0, $goUg1$reactariauseSwitch.useSwitch)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        // ReactNode type doesn't allow function children.
        children: typeof props.children === 'function' ? true : props.children
    }, state, inputRef);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($goUg1$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $f78e777132fa7bc2$export$5cc1518e1ec171c,
                state
            ],
            [
                $f78e777132fa7bc2$var$InternalSwitchContext,
                {
                    ...aria,
                    inputRef: inputRef,
                    defaultClassName: 'react-aria-Switch'
                }
            ]
        ]
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($goUg1$react))).createElement($f78e777132fa7bc2$export$72111f742dee7cb8, {
        ...props,
        ref: ref
    }));
});
const $f78e777132fa7bc2$var$InternalSwitchContext = /*#__PURE__*/ (0, $goUg1$react.createContext)(null);
const $f78e777132fa7bc2$export$208c2e617baf9fc3 = /*#__PURE__*/ (0, $goUg1$react.forwardRef)(function Switch(props, ref) {
    let { inputRef: userProvidedInputRef = null, ...otherProps } = props;
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(otherProps, ref, $f78e777132fa7bc2$export$3e1405298f0cafda);
    let { validationBehavior: formValidationBehavior } = (0, $048d76b84370f141$exports.useSlottedContext)((0, $5adc12e2ce73be8f$exports.FormContext)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let inputRef = (0, $goUg1$reactariauseObjectRef.useObjectRef)((0, $goUg1$reactariamergeRefs.mergeRefs)(userProvidedInputRef, props.inputRef !== undefined ? props.inputRef : null));
    let state = (0, $goUg1$reactstatelyuseToggleState.useToggleState)(props);
    let aria = (0, $goUg1$reactariauseSwitch.useSwitch)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        // ReactNode type doesn't allow function children.
        children: typeof props.children === 'function' ? true : props.children,
        validationBehavior: validationBehavior
    }, state, inputRef);
    let { descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isSelected: isSelected, isDisabled: isDisabled, isReadOnly: isReadOnly, isInvalid: isInvalid, validationDetails: validationDetails, validationErrors: validationErrors } = aria;
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $goUg1$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($goUg1$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $goUg1$reactariamergeProps.mergeProps)(DOMProps, renderProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-selected": isSelected || undefined,
        "data-disabled": isDisabled || undefined,
        "data-readonly": isReadOnly || undefined,
        "data-invalid": isInvalid || undefined,
        "data-required": props.isRequired || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($goUg1$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $f78e777132fa7bc2$export$5cc1518e1ec171c,
                state
            ],
            [
                $f78e777132fa7bc2$var$InternalSwitchContext,
                {
                    ...aria,
                    inputRef: inputRef,
                    defaultClassName: 'react-aria-SwitchButton',
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
            [
                (0, $862aa7df04d8fa76$exports.FieldErrorContext),
                {
                    isInvalid: isInvalid,
                    validationDetails: validationDetails,
                    validationErrors: validationErrors
                }
            ]
        ]
    }, renderProps.children));
});
const $f78e777132fa7bc2$export$72111f742dee7cb8 = /*#__PURE__*/ (0, $goUg1$react.forwardRef)(function SwitchButton(props, ref) {
    let { labelProps: labelProps, inputProps: inputProps, isSelected: isSelected, isDisabled: isDisabled, isReadOnly: isReadOnly, isPressed: isPressed, isInvalid: isInvalid, inputRef: inputRef, defaultClassName: defaultClassName, isRequired: isRequired } = (0, $goUg1$react.useContext)($f78e777132fa7bc2$var$InternalSwitchContext);
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $goUg1$reactariauseFocusRing.useFocusRing)();
    let isInteractionDisabled = isDisabled || isReadOnly;
    let state = (0, $goUg1$react.useContext)($f78e777132fa7bc2$export$5cc1518e1ec171c);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $goUg1$reactariauseHover.useHover)({
        ...props,
        isDisabled: isInteractionDisabled
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
            isReadOnly: isReadOnly,
            isInvalid: isInvalid,
            isRequired: isRequired || false,
            state: state
        }
    });
    let DOMProps = (0, $goUg1$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($goUg1$react))).createElement((0, $048d76b84370f141$exports.dom).label, {
        ...(0, $goUg1$reactariamergeProps.mergeProps)(DOMProps, labelProps, hoverProps, renderProps),
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
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($goUg1$react))).createElement((0, $goUg1$reactariaVisuallyHidden.VisuallyHidden), {
        elementType: "span"
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($goUg1$react))).createElement("input", {
        ...(0, $goUg1$reactariamergeProps.mergeProps)(inputProps, focusProps),
        ref: inputRef
    })), renderProps.children);
});


//# sourceMappingURL=Switch.cjs.map
