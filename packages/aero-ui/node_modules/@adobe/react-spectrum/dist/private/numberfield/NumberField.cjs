var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $b93966d678e0af07$exports = require("../label/Field.cjs");
var $b7aad1ed7fcdf769$exports = require("./StepButton.cjs");
require("../stepper_vars.css");
var $15de4d4dab96ad82$exports = require("../stepper_vars_css.cjs");
var $827dbb466e199966$exports = require("../textfield/TextFieldBase.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $1af2ca8553741739$exports = require("../form/Form.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $jQygj$reactariauseNumberField = require("react-aria/useNumberField");
var $jQygj$reactariaFocusRing = require("react-aria/FocusRing");
var $jQygj$reactariamergeProps = require("react-aria/mergeProps");
var $jQygj$reactstatelyuseNumberFieldState = require("react-stately/useNumberFieldState");
var $jQygj$react = require("react");
var $jQygj$reactariauseHover = require("react-aria/useHover");
var $jQygj$reactariaI18nProvider = require("react-aria/I18nProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "NumberField", function () { return $01d51ff4db71228b$export$63c5fa0b2fdccd2e; });
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















const $01d51ff4db71228b$export$63c5fa0b2fdccd2e = /*#__PURE__*/ (0, ($parcel$interopDefault($jQygj$react))).forwardRef(function NumberField(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    props = (0, $1af2ca8553741739$exports.useFormProps)(props);
    let provider = (0, $544fc82701fc93e9$exports.useProvider)();
    let { isQuiet: isQuiet, isReadOnly: isReadOnly, isDisabled: isDisabled, hideStepper: hideStepper } = props;
    let { styleProps: style } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let { locale: locale } = (0, $jQygj$reactariaI18nProvider.useLocale)();
    let state = (0, $jQygj$reactstatelyuseNumberFieldState.useNumberFieldState)({
        ...props,
        locale: locale
    });
    let inputRef = (0, $jQygj$react.useRef)(null);
    let domRef = (0, $65aea7b37663976b$exports.useFocusableRef)(ref, inputRef);
    let { groupProps: groupProps, labelProps: labelProps, inputProps: inputProps, incrementButtonProps: incrementButtonProps, decrementButtonProps: decrementButtonProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isInvalid: isInvalid, validationErrors: validationErrors, validationDetails: validationDetails } = (0, $jQygj$reactariauseNumberField.useNumberField)(props, state, inputRef);
    let isMobile = provider.scale === 'large';
    let showStepper = !hideStepper;
    let { isHovered: isHovered, hoverProps: hoverProps } = (0, $jQygj$reactariauseHover.useHover)({
        isDisabled: isDisabled
    });
    let validationState = props.validationState || (isInvalid ? 'invalid' : undefined);
    let className = (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'spectrum-Stepper', // because FocusRing won't pass along the className from Field, we have to handle that ourselves
    !props.label && style.className ? style.className : '', {
        'spectrum-Stepper--isQuiet': isQuiet,
        'is-disabled': isDisabled,
        'spectrum-Stepper--readonly': isReadOnly,
        'is-invalid': validationState === 'invalid' && !isDisabled,
        'spectrum-Stepper--showStepper': showStepper,
        'spectrum-Stepper--isMobile': isMobile,
        'is-hovered': isHovered
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($jQygj$react))).createElement((0, $b93966d678e0af07$exports.Field), {
        ...props,
        descriptionProps: descriptionProps,
        errorMessageProps: errorMessageProps,
        isInvalid: isInvalid,
        validationErrors: validationErrors,
        validationDetails: validationDetails,
        labelProps: labelProps,
        ref: domRef,
        wrapperClassName: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'spectrum-Stepper-container', {
            'spectrum-Stepper-container--isMobile': isMobile
        })
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($jQygj$react))).createElement($01d51ff4db71228b$var$NumberFieldInput, {
        ...props,
        groupProps: (0, $jQygj$reactariamergeProps.mergeProps)(groupProps, hoverProps),
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
const $01d51ff4db71228b$var$NumberFieldInput = /*#__PURE__*/ (0, ($parcel$interopDefault($jQygj$react))).forwardRef(function NumberFieldInput(props, ref) {
    let { groupProps: groupProps, inputProps: inputProps, inputRef: inputRef, incrementProps: incrementProps, decrementProps: decrementProps, className: className, style: style, autoFocus: autoFocus, isQuiet: isQuiet, isDisabled: isDisabled, hideStepper: hideStepper, validationState: validationState, name: name, state: state } = props;
    let showStepper = !hideStepper;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($jQygj$react))).createElement((0, $jQygj$reactariaFocusRing.FocusRing), {
        within: true,
        isTextInput: true,
        focusClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'is-focused'),
        focusRingClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($jQygj$react))).createElement("div", {
        ...groupProps,
        ref: ref,
        style: style,
        className: className
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($jQygj$react))).createElement((0, $827dbb466e199966$exports.TextFieldBase), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'spectrum-Stepper-field'),
        inputClassName: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'spectrum-Stepper-input'),
        validationIconClassName: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'spectrum-Stepper-icon'),
        isQuiet: isQuiet,
        inputRef: inputRef,
        validationState: validationState,
        inputProps: inputProps,
        isDisabled: isDisabled,
        disableFocusRing: true
    }), showStepper && /*#__PURE__*/ (0, ($parcel$interopDefault($jQygj$react))).createElement((0, ($parcel$interopDefault($jQygj$react))).Fragment, null, /*#__PURE__*/ (0, ($parcel$interopDefault($jQygj$react))).createElement((0, $b7aad1ed7fcdf769$exports.StepButton), {
        direction: "up",
        isQuiet: isQuiet,
        ...incrementProps
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($jQygj$react))).createElement((0, $b7aad1ed7fcdf769$exports.StepButton), {
        direction: "down",
        isQuiet: isQuiet,
        ...decrementProps
    })), name && /*#__PURE__*/ (0, ($parcel$interopDefault($jQygj$react))).createElement("input", {
        type: "hidden",
        name: name,
        form: props.form,
        value: isNaN(state.numberValue) ? '' : state.numberValue
    })));
});


//# sourceMappingURL=NumberField.cjs.map
