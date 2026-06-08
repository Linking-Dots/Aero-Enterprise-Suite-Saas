import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {Field as $adcd096854d27620$export$a455218a85c89869} from "../label/Field.mjs";
import {StepButton as $9f85b9d1ee72dcc2$export$b2f6b60c1d32d6aa} from "./StepButton.mjs";
import "../stepper_vars.css";
import $b7rOY$stepper_vars_cssmjs from "../stepper_vars_css.mjs";
import {TextFieldBase as $b312f2102feb9487$export$d22444a338b6e3c2} from "../textfield/TextFieldBase.mjs";
import {useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {useFormProps as $c29c48d4ef19ffc4$export$a6b5be5c6b451665} from "../form/Form.mjs";
import {useProvider as $71dfb0e0358a12de$export$693cdb10cec23617, useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useNumberField as $b7rOY$useNumberField} from "react-aria/useNumberField";
import {FocusRing as $b7rOY$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $b7rOY$mergeProps} from "react-aria/mergeProps";
import {useNumberFieldState as $b7rOY$useNumberFieldState} from "react-stately/useNumberFieldState";
import $b7rOY$react, {useRef as $b7rOY$useRef} from "react";
import {useHover as $b7rOY$useHover} from "react-aria/useHover";
import {useLocale as $b7rOY$useLocale} from "react-aria/I18nProvider";


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















const $cc4546b8c67ac930$export$63c5fa0b2fdccd2e = /*#__PURE__*/ (0, $b7rOY$react).forwardRef(function NumberField(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    props = (0, $c29c48d4ef19ffc4$export$a6b5be5c6b451665)(props);
    let provider = (0, $71dfb0e0358a12de$export$693cdb10cec23617)();
    let { isQuiet: isQuiet, isReadOnly: isReadOnly, isDisabled: isDisabled, hideStepper: hideStepper } = props;
    let { styleProps: style } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let { locale: locale } = (0, $b7rOY$useLocale)();
    let state = (0, $b7rOY$useNumberFieldState)({
        ...props,
        locale: locale
    });
    let inputRef = (0, $b7rOY$useRef)(null);
    let domRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref, inputRef);
    let { groupProps: groupProps, labelProps: labelProps, inputProps: inputProps, incrementButtonProps: incrementButtonProps, decrementButtonProps: decrementButtonProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isInvalid: isInvalid, validationErrors: validationErrors, validationDetails: validationDetails } = (0, $b7rOY$useNumberField)(props, state, inputRef);
    let isMobile = provider.scale === 'large';
    let showStepper = !hideStepper;
    let { isHovered: isHovered, hoverProps: hoverProps } = (0, $b7rOY$useHover)({
        isDisabled: isDisabled
    });
    let validationState = props.validationState || (isInvalid ? 'invalid' : undefined);
    let className = (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($b7rOY$stepper_vars_cssmjs))), 'spectrum-Stepper', // because FocusRing won't pass along the className from Field, we have to handle that ourselves
    !props.label && style.className ? style.className : '', {
        'spectrum-Stepper--isQuiet': isQuiet,
        'is-disabled': isDisabled,
        'spectrum-Stepper--readonly': isReadOnly,
        'is-invalid': validationState === 'invalid' && !isDisabled,
        'spectrum-Stepper--showStepper': showStepper,
        'spectrum-Stepper--isMobile': isMobile,
        'is-hovered': isHovered
    });
    return /*#__PURE__*/ (0, $b7rOY$react).createElement((0, $adcd096854d27620$export$a455218a85c89869), {
        ...props,
        descriptionProps: descriptionProps,
        errorMessageProps: errorMessageProps,
        isInvalid: isInvalid,
        validationErrors: validationErrors,
        validationDetails: validationDetails,
        labelProps: labelProps,
        ref: domRef,
        wrapperClassName: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($b7rOY$stepper_vars_cssmjs))), 'spectrum-Stepper-container', {
            'spectrum-Stepper-container--isMobile': isMobile
        })
    }, /*#__PURE__*/ (0, $b7rOY$react).createElement($cc4546b8c67ac930$var$NumberFieldInput, {
        ...props,
        groupProps: (0, $b7rOY$mergeProps)(groupProps, hoverProps),
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
const $cc4546b8c67ac930$var$NumberFieldInput = /*#__PURE__*/ (0, $b7rOY$react).forwardRef(function NumberFieldInput(props, ref) {
    let { groupProps: groupProps, inputProps: inputProps, inputRef: inputRef, incrementProps: incrementProps, decrementProps: decrementProps, className: className, style: style, autoFocus: autoFocus, isQuiet: isQuiet, isDisabled: isDisabled, hideStepper: hideStepper, validationState: validationState, name: name, state: state } = props;
    let showStepper = !hideStepper;
    return /*#__PURE__*/ (0, $b7rOY$react).createElement((0, $b7rOY$FocusRing), {
        within: true,
        isTextInput: true,
        focusClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($b7rOY$stepper_vars_cssmjs))), 'is-focused'),
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($b7rOY$stepper_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $b7rOY$react).createElement("div", {
        ...groupProps,
        ref: ref,
        style: style,
        className: className
    }, /*#__PURE__*/ (0, $b7rOY$react).createElement((0, $b312f2102feb9487$export$d22444a338b6e3c2), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($b7rOY$stepper_vars_cssmjs))), 'spectrum-Stepper-field'),
        inputClassName: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($b7rOY$stepper_vars_cssmjs))), 'spectrum-Stepper-input'),
        validationIconClassName: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($b7rOY$stepper_vars_cssmjs))), 'spectrum-Stepper-icon'),
        isQuiet: isQuiet,
        inputRef: inputRef,
        validationState: validationState,
        inputProps: inputProps,
        isDisabled: isDisabled,
        disableFocusRing: true
    }), showStepper && /*#__PURE__*/ (0, $b7rOY$react).createElement((0, $b7rOY$react).Fragment, null, /*#__PURE__*/ (0, $b7rOY$react).createElement((0, $9f85b9d1ee72dcc2$export$b2f6b60c1d32d6aa), {
        direction: "up",
        isQuiet: isQuiet,
        ...incrementProps
    }), /*#__PURE__*/ (0, $b7rOY$react).createElement((0, $9f85b9d1ee72dcc2$export$b2f6b60c1d32d6aa), {
        direction: "down",
        isQuiet: isQuiet,
        ...decrementProps
    })), name && /*#__PURE__*/ (0, $b7rOY$react).createElement("input", {
        type: "hidden",
        name: name,
        form: props.form,
        value: isNaN(state.numberValue) ? '' : state.numberValue
    })));
});


export {$cc4546b8c67ac930$export$63c5fa0b2fdccd2e as NumberField};
//# sourceMappingURL=NumberField.mjs.map
