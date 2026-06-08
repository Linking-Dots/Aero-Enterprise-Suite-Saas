import {ButtonContext as $fc203795b9b363cd$export$24d547caef80ccd1} from "./Button.js";
import {CalendarContext as $62acebab8283d780$export$3b805cea1f178355, RangeCalendarContext as $62acebab8283d780$export$233dd9682e1ad64b} from "./Calendar.js";
import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, removeDataAttributes as $b7b7a92703138c9b$export$ef03459518577ad4, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3, useSlot as $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8, useSlottedContext as $b7b7a92703138c9b$export$fabf2dc03a41866e} from "./utils.js";
import {DateFieldContext as $ebd46e18226db4de$export$7b3e670c86da5fe8} from "./DateField.js";
import {DialogContext as $acf8e70c2f419f18$export$8b93a07348a7730c, OverlayTriggerStateContext as $acf8e70c2f419f18$export$d2f961adcb0afbe} from "./Dialog.js";
import {FieldErrorContext as $6567560e1d9cc847$export$ff05c3ac10437e03} from "./FieldError.js";
import {FormContext as $c7332d4a2d191cd2$export$c24727297075ec6a} from "./Form.js";
import {GroupContext as $2e357e4f16c05be6$export$f9c6924e160136d1} from "./Group.js";
import {HiddenDateInput as $7133f72f3bca7442$export$eefa3e19139f00f3} from "./HiddenDateInput.js";
import {LabelContext as $3e4839e5b30e7b17$export$75b6ee27786ba447} from "./Label.js";
import {PopoverContext as $03df2f2d3cffb62f$export$9b9a0cd73afb7ca4} from "./Popover.js";
import {TextContext as $20d769b1e2b13352$export$9afb8bc826b033ea} from "./Text.js";
import {useDatePicker as $eiyUW$useDatePicker} from "react-aria/useDatePicker";
import {useDateRangePicker as $eiyUW$useDateRangePicker} from "react-aria/useDateRangePicker";
import {useDatePickerState as $eiyUW$useDatePickerState} from "react-stately/useDatePickerState";
import {useDateRangePickerState as $eiyUW$useDateRangePickerState} from "react-stately/useDateRangePickerState";
import {filterDOMProps as $eiyUW$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $eiyUW$mergeProps} from "react-aria/mergeProps";
import $eiyUW$react, {createContext as $eiyUW$createContext, forwardRef as $eiyUW$forwardRef, useRef as $eiyUW$useRef} from "react";
import {useFocusRing as $eiyUW$useFocusRing} from "react-aria/useFocusRing";

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



















const $97391356e321081b$export$cf316c7f3b44c11e = /*#__PURE__*/ (0, $eiyUW$createContext)(null);
const $97391356e321081b$export$8282edba42ee28a = /*#__PURE__*/ (0, $eiyUW$createContext)(null);
const $97391356e321081b$export$50a10c048fdcdee9 = /*#__PURE__*/ (0, $eiyUW$createContext)(null);
const $97391356e321081b$export$80d7ae1f804790be = /*#__PURE__*/ (0, $eiyUW$createContext)(null);
// Contexts to clear inside the popover.
const $97391356e321081b$var$CLEAR_CONTEXTS = [
    (0, $2e357e4f16c05be6$export$f9c6924e160136d1),
    (0, $fc203795b9b363cd$export$24d547caef80ccd1),
    (0, $3e4839e5b30e7b17$export$75b6ee27786ba447),
    (0, $20d769b1e2b13352$export$9afb8bc826b033ea)
];
const $97391356e321081b$export$5109c6dd95d8fb00 = /*#__PURE__*/ (0, $eiyUW$forwardRef)(function DatePicker(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $97391356e321081b$export$cf316c7f3b44c11e);
    let { validationBehavior: formValidationBehavior } = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)((0, $c7332d4a2d191cd2$export$c24727297075ec6a)) || {};
    var _props_validationBehavior, _ref;
    let validationBehavior = (_ref = (_props_validationBehavior = props.validationBehavior) !== null && _props_validationBehavior !== void 0 ? _props_validationBehavior : formValidationBehavior) !== null && _ref !== void 0 ? _ref : 'native';
    let state = (0, $eiyUW$useDatePickerState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let groupRef = (0, $eiyUW$useRef)(null);
    let [labelRef, label] = (0, $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { groupProps: groupProps, labelProps: labelProps, fieldProps: fieldProps, buttonProps: buttonProps, dialogProps: dialogProps, calendarProps: calendarProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $eiyUW$useDatePicker)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, groupRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $eiyUW$useFocusRing)({
        within: true
    });
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        values: {
            state: state,
            isFocusWithin: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: props.isDisabled || false,
            isInvalid: state.isInvalid,
            isOpen: state.isOpen,
            isReadOnly: props.isReadOnly || false,
            isRequired: props.isRequired || false
        },
        defaultClassName: 'react-aria-DatePicker'
    });
    let DOMProps = (0, $eiyUW$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $eiyUW$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $97391356e321081b$export$50a10c048fdcdee9,
                state
            ],
            [
                (0, $2e357e4f16c05be6$export$f9c6924e160136d1),
                {
                    ...groupProps,
                    ref: groupRef,
                    isInvalid: state.isInvalid
                }
            ],
            [
                (0, $ebd46e18226db4de$export$7b3e670c86da5fe8),
                fieldProps
            ],
            [
                (0, $fc203795b9b363cd$export$24d547caef80ccd1),
                {
                    ...buttonProps,
                    isPressed: state.isOpen
                }
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
                (0, $62acebab8283d780$export$3b805cea1f178355),
                calendarProps
            ],
            [
                (0, $acf8e70c2f419f18$export$d2f961adcb0afbe),
                state
            ],
            [
                (0, $03df2f2d3cffb62f$export$9b9a0cd73afb7ca4),
                {
                    trigger: 'DatePicker',
                    triggerRef: groupRef,
                    placement: 'bottom start',
                    clearContexts: $97391356e321081b$var$CLEAR_CONTEXTS
                }
            ],
            [
                (0, $acf8e70c2f419f18$export$8b93a07348a7730c),
                dialogProps
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
    }, /*#__PURE__*/ (0, $eiyUW$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $eiyUW$mergeProps)(DOMProps, renderProps, focusProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focus-within": isFocused || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-readonly": props.isReadOnly || undefined,
        "data-required": props.isRequired || undefined,
        "data-open": state.isOpen || undefined
    }), /*#__PURE__*/ (0, $eiyUW$react).createElement((0, $7133f72f3bca7442$export$eefa3e19139f00f3), {
        autoComplete: props.autoComplete,
        name: props.name,
        isDisabled: props.isDisabled,
        state: state
    }));
});
const $97391356e321081b$export$17334619f3ac2224 = /*#__PURE__*/ (0, $eiyUW$forwardRef)(function DateRangePicker(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $97391356e321081b$export$8282edba42ee28a);
    let { validationBehavior: formValidationBehavior } = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)((0, $c7332d4a2d191cd2$export$c24727297075ec6a)) || {};
    var _props_validationBehavior, _ref;
    let validationBehavior = (_ref = (_props_validationBehavior = props.validationBehavior) !== null && _props_validationBehavior !== void 0 ? _props_validationBehavior : formValidationBehavior) !== null && _ref !== void 0 ? _ref : 'native';
    let state = (0, $eiyUW$useDateRangePickerState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let groupRef = (0, $eiyUW$useRef)(null);
    let [labelRef, label] = (0, $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { groupProps: groupProps, labelProps: labelProps, startFieldProps: startFieldProps, endFieldProps: endFieldProps, buttonProps: buttonProps, dialogProps: dialogProps, calendarProps: calendarProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $eiyUW$useDateRangePicker)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, groupRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $eiyUW$useFocusRing)({
        within: true
    });
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        values: {
            state: state,
            isFocusWithin: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: props.isDisabled || false,
            isInvalid: state.isInvalid,
            isOpen: state.isOpen,
            isReadOnly: props.isReadOnly || false,
            isRequired: props.isRequired || false
        },
        defaultClassName: 'react-aria-DateRangePicker'
    });
    let DOMProps = (0, $eiyUW$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $eiyUW$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $97391356e321081b$export$80d7ae1f804790be,
                state
            ],
            [
                (0, $2e357e4f16c05be6$export$f9c6924e160136d1),
                {
                    ...groupProps,
                    ref: groupRef,
                    isInvalid: state.isInvalid
                }
            ],
            [
                (0, $fc203795b9b363cd$export$24d547caef80ccd1),
                {
                    ...buttonProps,
                    isPressed: state.isOpen
                }
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
                (0, $62acebab8283d780$export$233dd9682e1ad64b),
                calendarProps
            ],
            [
                (0, $acf8e70c2f419f18$export$d2f961adcb0afbe),
                state
            ],
            [
                (0, $03df2f2d3cffb62f$export$9b9a0cd73afb7ca4),
                {
                    trigger: 'DateRangePicker',
                    triggerRef: groupRef,
                    placement: 'bottom start',
                    clearContexts: $97391356e321081b$var$CLEAR_CONTEXTS
                }
            ],
            [
                (0, $acf8e70c2f419f18$export$8b93a07348a7730c),
                dialogProps
            ],
            [
                (0, $ebd46e18226db4de$export$7b3e670c86da5fe8),
                {
                    slots: {
                        start: startFieldProps,
                        end: endFieldProps
                    }
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
    }, /*#__PURE__*/ (0, $eiyUW$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $eiyUW$mergeProps)(DOMProps, renderProps, focusProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focus-within": isFocused || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-readonly": props.isReadOnly || undefined,
        "data-required": props.isRequired || undefined,
        "data-open": state.isOpen || undefined
    }));
});


export {$97391356e321081b$export$cf316c7f3b44c11e as DatePickerContext, $97391356e321081b$export$8282edba42ee28a as DateRangePickerContext, $97391356e321081b$export$50a10c048fdcdee9 as DatePickerStateContext, $97391356e321081b$export$80d7ae1f804790be as DateRangePickerStateContext, $97391356e321081b$export$5109c6dd95d8fb00 as DatePicker, $97391356e321081b$export$17334619f3ac2224 as DateRangePicker};
//# sourceMappingURL=DatePicker.js.map
