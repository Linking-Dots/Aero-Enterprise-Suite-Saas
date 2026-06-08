import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {Field as $3967792f95357356$export$a455218a85c89869} from "../label/Field.js";
import {StepButton as $f06f822f6b0bbb02$export$b2f6b60c1d32d6aa} from "./StepButton.js";
import "../stepper_vars.css";
import $3SBAj$stepper_vars_cssmjs from "../stepper_vars_css.mjs";
import {TextFieldBase as $1f88830e88ee8f61$export$d22444a338b6e3c2} from "../textfield/TextFieldBase.js";
import {useFocusableRef as $c234463e9ef56637$export$96a734597687c040} from "../utils/useDOMRef.js";
import {useFormProps as $d23ca6800ac02cf1$export$a6b5be5c6b451665} from "../form/Form.js";
import {useProvider as $089943c7a219141c$export$693cdb10cec23617, useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useNumberField as $3SBAj$useNumberField} from "react-aria/useNumberField";
import {FocusRing as $3SBAj$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $3SBAj$mergeProps} from "react-aria/mergeProps";
import {useNumberFieldState as $3SBAj$useNumberFieldState} from "react-stately/useNumberFieldState";
import $3SBAj$react, {useRef as $3SBAj$useRef} from "react";
import {useHover as $3SBAj$useHover} from "react-aria/useHover";
import {useLocale as $3SBAj$useLocale} from "react-aria/I18nProvider";


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















const $e8146d8fdc8bfe0f$export$63c5fa0b2fdccd2e = /*#__PURE__*/ (0, $3SBAj$react).forwardRef(function NumberField(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    props = (0, $d23ca6800ac02cf1$export$a6b5be5c6b451665)(props);
    let provider = (0, $089943c7a219141c$export$693cdb10cec23617)();
    let { isQuiet: isQuiet, isReadOnly: isReadOnly, isDisabled: isDisabled, hideStepper: hideStepper } = props;
    let { styleProps: style } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let { locale: locale } = (0, $3SBAj$useLocale)();
    let state = (0, $3SBAj$useNumberFieldState)({
        ...props,
        locale: locale
    });
    let inputRef = (0, $3SBAj$useRef)(null);
    let domRef = (0, $c234463e9ef56637$export$96a734597687c040)(ref, inputRef);
    let { groupProps: groupProps, labelProps: labelProps, inputProps: inputProps, incrementButtonProps: incrementButtonProps, decrementButtonProps: decrementButtonProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isInvalid: isInvalid, validationErrors: validationErrors, validationDetails: validationDetails } = (0, $3SBAj$useNumberField)(props, state, inputRef);
    let isMobile = provider.scale === 'large';
    let showStepper = !hideStepper;
    let { isHovered: isHovered, hoverProps: hoverProps } = (0, $3SBAj$useHover)({
        isDisabled: isDisabled
    });
    let validationState = props.validationState || (isInvalid ? 'invalid' : undefined);
    let className = (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3SBAj$stepper_vars_cssmjs))), 'spectrum-Stepper', // because FocusRing won't pass along the className from Field, we have to handle that ourselves
    !props.label && style.className ? style.className : '', {
        'spectrum-Stepper--isQuiet': isQuiet,
        'is-disabled': isDisabled,
        'spectrum-Stepper--readonly': isReadOnly,
        'is-invalid': validationState === 'invalid' && !isDisabled,
        'spectrum-Stepper--showStepper': showStepper,
        'spectrum-Stepper--isMobile': isMobile,
        'is-hovered': isHovered
    });
    return /*#__PURE__*/ (0, $3SBAj$react).createElement((0, $3967792f95357356$export$a455218a85c89869), {
        ...props,
        descriptionProps: descriptionProps,
        errorMessageProps: errorMessageProps,
        isInvalid: isInvalid,
        validationErrors: validationErrors,
        validationDetails: validationDetails,
        labelProps: labelProps,
        ref: domRef,
        wrapperClassName: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3SBAj$stepper_vars_cssmjs))), 'spectrum-Stepper-container', {
            'spectrum-Stepper-container--isMobile': isMobile
        })
    }, /*#__PURE__*/ (0, $3SBAj$react).createElement($e8146d8fdc8bfe0f$var$NumberFieldInput, {
        ...props,
        groupProps: (0, $3SBAj$mergeProps)(groupProps, hoverProps),
        inputProps: inputProps,
        inputRef: inputRef,
        incrementProps: incrementButtonProps,
        decrementProps: decrementButtonProps,
        className: className,
        style: style,
        state: state,
        validationState: validationState
    }));
});
const $e8146d8fdc8bfe0f$var$NumberFieldInput = /*#__PURE__*/ (0, $3SBAj$react).forwardRef(function NumberFieldInput(props, ref) {
    let { groupProps: groupProps, inputProps: inputProps, inputRef: inputRef, incrementProps: incrementProps, decrementProps: decrementProps, className: className, style: style, autoFocus: autoFocus, isQuiet: isQuiet, isDisabled: isDisabled, hideStepper: hideStepper, validationState: validationState, name: name, state: state } = props;
    let showStepper = !hideStepper;
    return /*#__PURE__*/ (0, $3SBAj$react).createElement((0, $3SBAj$FocusRing), {
        within: true,
        isTextInput: true,
        focusClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3SBAj$stepper_vars_cssmjs))), 'is-focused'),
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3SBAj$stepper_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $3SBAj$react).createElement("div", {
        ...groupProps,
        ref: ref,
        style: style,
        className: className
    }, /*#__PURE__*/ (0, $3SBAj$react).createElement((0, $1f88830e88ee8f61$export$d22444a338b6e3c2), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3SBAj$stepper_vars_cssmjs))), 'spectrum-Stepper-field'),
        inputClassName: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3SBAj$stepper_vars_cssmjs))), 'spectrum-Stepper-input'),
        validationIconClassName: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3SBAj$stepper_vars_cssmjs))), 'spectrum-Stepper-icon'),
        isQuiet: isQuiet,
        inputRef: inputRef,
        validationState: validationState,
        inputProps: inputProps,
        isDisabled: isDisabled,
        disableFocusRing: true
    }), showStepper && /*#__PURE__*/ (0, $3SBAj$react).createElement((0, $3SBAj$react).Fragment, null, /*#__PURE__*/ (0, $3SBAj$react).createElement((0, $f06f822f6b0bbb02$export$b2f6b60c1d32d6aa), {
        direction: "up",
        isQuiet: isQuiet,
        ...incrementProps
    }), /*#__PURE__*/ (0, $3SBAj$react).createElement((0, $f06f822f6b0bbb02$export$b2f6b60c1d32d6aa), {
        direction: "down",
        isQuiet: isQuiet,
        ...decrementProps
    })), name && /*#__PURE__*/ (0, $3SBAj$react).createElement("input", {
        type: "hidden",
        name: name,
        form: props.form,
        value: isNaN(state.numberValue) ? '' : state.numberValue
    })));
});


export {$e8146d8fdc8bfe0f$export$63c5fa0b2fdccd2e as NumberField};
//# sourceMappingURL=NumberField.js.map
